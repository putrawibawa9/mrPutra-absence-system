<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseItemSystemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function item(string $name, string $categoryName, string $behavior = ExpenseCategory::COST_VARIABLE, bool $system = false): ExpenseItem
    {
        $category = ExpenseCategory::query()->firstOrCreate(
            ['name' => $categoryName],
            ['is_active' => true, 'cost_behavior' => $behavior, 'is_system' => $system]
        );

        return ExpenseItem::query()->create([
            'expense_category_id' => $category->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_category_always_follows_the_item_not_the_request(): void
    {
        $admin = $this->admin();
        $software = $this->item('Hostinger', 'Software & Langganan');
        // Kategori lain yang "salah" — coba kirim lewat request, harus diabaikan.
        $marketing = ExpenseCategory::query()->create(['name' => 'Sosmed & Marketing', 'is_active' => true]);

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_item_id' => $software->id,
            'expense_category_id' => $marketing->id, // harus diabaikan
            'title' => 'Bayar hosting',
            'amount' => 191697,
            'expense_date' => '2026-09-14',
            'confirmed' => 1,
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::query()->firstOrFail();
        $this->assertSame($software->id, $expense->expense_item_id);
        $this->assertSame($software->expense_category_id, $expense->expense_category_id);
        $this->assertNotSame($marketing->id, $expense->expense_category_id);
    }

    public function test_title_is_optional_and_falls_back_to_item_name(): void
    {
        $admin = $this->admin();
        $item = $this->item('Claude Pro', 'Software & Langganan');

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_item_id' => $item->id,
            'amount' => 370000,
            'expense_date' => '2026-09-14',
            'confirmed' => 1,
        ])->assertRedirect(route('expenses.index'));

        $this->assertSame('Claude Pro', Expense::query()->firstOrFail()->title);
    }

    public function test_fee_guru_item_cannot_be_used_for_manual_input(): void
    {
        $admin = $this->admin();
        $feeItem = $this->item('Fee Guru', ExpenseCategory::FEE_GURU_NAME, ExpenseCategory::COST_VARIABLE, true);

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_item_id' => $feeItem->id,
            'amount' => 40000,
            'expense_date' => '2026-09-14',
            'confirmed' => 1,
        ])->assertSessionHasErrors('expense_item_id');

        $this->assertSame(0, Expense::query()->count());
    }

    public function test_model_guard_blocks_fee_guru_expense_without_attendance(): void
    {
        $feeCategory = ExpenseCategory::query()->create([
            'name' => ExpenseCategory::FEE_GURU_NAME,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->expectException(\RuntimeException::class);

        Expense::query()->create([
            'expense_category_id' => $feeCategory->id,
            'title' => 'Fee manual',
            'amount' => 40000,
            'expense_date' => '2026-09-14',
        ]);
    }

    public function test_inactive_item_is_rejected(): void
    {
        $admin = $this->admin();
        $item = $this->item('Gamma AI', 'Software & Langganan');
        $item->update(['is_active' => false]);

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_item_id' => $item->id,
            'amount' => 99000,
            'expense_date' => '2026-09-14',
            'confirmed' => 1,
        ])->assertSessionHasErrors('expense_item_id');
    }

    public function test_expense_item_crud_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('expense-items.index'))->assertForbidden();
    }

    public function test_reports_and_dashboard_still_work_after_change(): void
    {
        $admin = $this->admin();
        $item = $this->item('Kuota Internet', 'Operasional');
        Expense::query()->create([
            'expense_category_id' => $item->expense_category_id,
            'expense_item_id' => $item->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Kuota',
            'amount' => 50000,
            'expense_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('cash-flow.index'))->assertOk();
        $this->actingAs($admin)->get(route('reports.opex'))->assertOk();
        $this->actingAs($admin)->get(route('reports.review'))->assertOk();
        $this->actingAs($admin)->get(route('expenses.index'))->assertOk();
    }
}
