<?php

namespace Tests\Feature;

use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCommitment;
use App\Models\ExpenseItem;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\ExpenseItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecategorizeExpensesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function setUpReferenceData(): void
    {
        $this->seed(ExpenseCategorySeeder::class);
        $this->seed(ExpenseItemSeeder::class);
    }

    private function operationalCategoryId(): int
    {
        return ExpenseCategory::query()->where('name', 'Operasional')->value('id');
    }

    public function test_dry_run_does_not_change_data_but_writes_csv(): void
    {
        $this->setUpReferenceData();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $wrong = Expense::query()->create([
            'expense_category_id' => $this->operationalCategoryId(),
            'created_by_user_id' => $admin->id,
            'title' => 'Bayar Hostinger 1 tahun',
            'amount' => 191697,
            'expense_date' => '2026-06-01',
        ]);

        $before = glob(storage_path('app/recategorize_*.csv'));

        $this->artisan('expenses:recategorize')->assertSuccessful();

        // Data tidak berubah.
        $this->assertNull($wrong->fresh()->expense_item_id);
        $this->assertSame($this->operationalCategoryId(), (int) $wrong->fresh()->expense_category_id);

        // CSV baru dibuat.
        $after = glob(storage_path('app/recategorize_*.csv'));
        $this->assertGreaterThan(count($before), count($after));

        // Bersihkan file uji.
        foreach (array_diff($after, $before) as $file) {
            @unlink($file);
        }
    }

    public function test_apply_assigns_items_and_keeps_one_category_per_installment_group(): void
    {
        $this->setUpReferenceData();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $wrongCat = $this->operationalCategoryId();

        $hosting = Expense::query()->create([
            'expense_category_id' => $wrongCat,
            'created_by_user_id' => $admin->id,
            'title' => 'Bayar Hostinger 1 tahun',
            'amount' => 191697,
            'expense_date' => '2026-06-01',
        ]);

        // Grup cicilan dengan kategori "salah" (Operasional) — harus jadi satu item/kategori.
        $group = (string) Str::uuid();
        foreach ([1, 2] as $i) {
            Expense::query()->create([
                'expense_category_id' => $wrongCat,
                'created_by_user_id' => $admin->id,
                'title' => 'CapCut Pro 1 Tahun (Cicilan '.$i.'/2)',
                'amount' => 75000,
                'expense_date' => '2026-09-1'.$i,
                'amortization_group' => $group,
                'amortization_index' => $i,
                'amortization_total' => 2,
            ]);
        }

        $this->artisan('expenses:recategorize --apply')->assertSuccessful();

        // Hosting → item Hostinger / kategori Software & Langganan.
        $hostItem = ExpenseItem::query()->where('name', 'Hostinger')->firstOrFail();
        $this->assertSame($hostItem->id, (int) $hosting->fresh()->expense_item_id);
        $this->assertSame($hostItem->expense_category_id, (int) $hosting->fresh()->expense_category_id);

        // Grup cicilan: satu item & satu kategori untuk semua baris + komitmen dibuat.
        $groupRows = Expense::query()->where('amortization_group', $group)->get();
        $this->assertSame(1, $groupRows->pluck('expense_item_id')->unique()->count());
        $this->assertSame(1, $groupRows->pluck('expense_category_id')->unique()->count());
        $this->assertSame(1, ExpenseCommitment::query()->where('amortization_group', $group)->count());

        foreach (glob(storage_path('app/recategorize_*.csv')) as $file) {
            @unlink($file);
        }
    }

    public function test_fee_guru_linked_row_gets_fee_item_but_keeps_category(): void
    {
        $this->setUpReferenceData();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $feeCategoryId = ExpenseCategory::query()->where('name', ExpenseCategory::FEE_GURU_NAME)->value('id');
        $batch = AttendanceBatch::query()->create(['title' => 'Grup', 'teacher_id' => $teacher->id, 'date' => '2026-09-10']);

        $fee = Expense::query()->create([
            'expense_category_id' => $feeCategoryId,
            'created_by_user_id' => $admin->id,
            'teacher_user_id' => $teacher->id,
            'attendance_batch_id' => $batch->id,
            'title' => 'Fee guru grup',
            'amount' => 40000,
            'expense_date' => '2026-09-10',
        ]);

        $this->artisan('expenses:recategorize --apply')->assertSuccessful();

        $feeItem = ExpenseItem::query()->where('name', ExpenseCategory::FEE_GURU_NAME)->firstOrFail();
        $this->assertSame($feeItem->id, (int) $fee->fresh()->expense_item_id);
        $this->assertSame((int) $feeCategoryId, (int) $fee->fresh()->expense_category_id);

        foreach (glob(storage_path('app/recategorize_*.csv')) as $file) {
            @unlink($file);
        }
    }
}
