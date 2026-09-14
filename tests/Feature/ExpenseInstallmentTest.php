<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpenseInstallmentTest extends TestCase
{
    use RefreshDatabase;

    private function category(): ExpenseCategory
    {
        return ExpenseCategory::query()->create(['name' => 'Tools', 'is_active' => true]);
    }

    public function test_normal_expense_is_created_as_single_row(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cat = $this->category();

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_category_id' => $cat->id,
            'title' => 'Listrik',
            'amount' => 500000,
            'expense_date' => '2026-09-14',
            'installment_months' => 1,
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseCount('expenses', 1);
        $expense = Expense::query()->firstOrFail();
        $this->assertSame(500000, $expense->amount);
        $this->assertNull($expense->amortization_total);
    }

    public function test_installment_splits_into_monthly_rows_summing_to_total(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cat = $this->category();

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_category_id' => $cat->id,
            'title' => 'CapCut Pro Tahunan',
            'amount' => 1200000,
            'expense_date' => '2026-01-15',
            'installment_months' => 12,
        ])->assertRedirect(route('expenses.index'));

        $rows = Expense::query()->whereNotNull('amortization_group')->orderBy('amortization_index')->get();

        $this->assertCount(12, $rows);
        $this->assertSame(1200000, (int) $rows->sum('amount'));
        $this->assertTrue($rows->every(fn (Expense $e) => $e->amount === 100000));
        $this->assertSame(1, $rows->pluck('amortization_group')->unique()->count());
        $this->assertSame(12, $rows->first()->amortization_total);

        // Baris pertama = bulan pembelian; baris ke-2 = bulan berikutnya, dst.
        $this->assertSame('2026-01-15', $rows->first()->expense_date->toDateString());
        $this->assertSame('2026-02-15', $rows[1]->expense_date->toDateString());
        $this->assertStringContainsString('Cicilan 1/12', $rows->first()->title);
    }

    public function test_installment_remainder_is_distributed_to_first_months(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cat = $this->category();

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_category_id' => $cat->id,
            'title' => 'Paket',
            'amount' => 100,
            'expense_date' => '2026-01-01',
            'installment_months' => 3,
        ])->assertRedirect(route('expenses.index'));

        $amounts = Expense::query()->orderBy('amortization_index')->pluck('amount')->all();
        $this->assertSame([34, 33, 33], $amounts);
        $this->assertSame(100, array_sum($amounts));
    }

    public function test_only_one_monthly_slice_falls_in_the_purchase_month(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cat = $this->category();
        $start = Carbon::create(2026, 3, 10);

        $this->actingAs($admin)->post(route('expenses.store'), [
            'expense_category_id' => $cat->id,
            'title' => 'Langganan',
            'amount' => 1200000,
            'expense_date' => $start->toDateString(),
            'installment_months' => 12,
        ]);

        // Cash flow menjumlahkan expense per bulan; bulan pembelian hanya kena 1 porsi.
        $inPurchaseMonth = Expense::query()
            ->whereDate('expense_date', '>=', $start->copy()->startOfMonth()->toDateString())
            ->whereDate('expense_date', '<=', $start->copy()->endOfMonth()->toDateString())
            ->get();

        $this->assertCount(1, $inPurchaseMonth);
        $this->assertSame(100000, (int) $inPurchaseMonth->sum('amount'));
    }
}
