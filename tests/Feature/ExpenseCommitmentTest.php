<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCommitment;
use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCommitmentTest extends TestCase
{
    use RefreshDatabase;

    private function item(): ExpenseItem
    {
        $category = ExpenseCategory::query()->create(['name' => 'Software & Langganan', 'is_active' => true, 'cost_behavior' => ExpenseCategory::COST_FIXED]);

        return ExpenseItem::query()->create(['expense_category_id' => $category->id, 'name' => 'CapCut Pro', 'is_active' => true]);
    }

    public function test_commitment_generates_monthly_rows_with_remainder_on_last(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();

        $this->actingAs($admin)->post(route('expense-commitments.store'), [
            'expense_item_id' => $item->id,
            'name' => 'CapCut Pro 1 Tahun',
            'total_amount' => 100, // 100 / 3 = 33,33,34 (sisa di terakhir)
            'months' => 3,
            'start_date' => '2026-01-15',
        ])->assertRedirect(route('expense-commitments.index'));

        $rows = Expense::query()->whereNotNull('amortization_group')->orderBy('amortization_index')->get();

        $this->assertCount(3, $rows);
        $this->assertSame(100, (int) $rows->sum('amount'));
        $this->assertSame([33, 33, 34], $rows->pluck('amount')->map(fn ($a) => (int) $a)->all());
        // Semua baris satu item & satu kategori.
        $this->assertSame(1, $rows->pluck('expense_item_id')->unique()->count());
        $this->assertSame(1, $rows->pluck('expense_category_id')->unique()->count());
        $this->assertSame($item->expense_category_id, (int) $rows->first()->expense_category_id);
        $this->assertSame(1, ExpenseCommitment::query()->count());
    }

    public function test_grouped_installment_row_cannot_change_item_individually(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $otherCat = ExpenseCategory::query()->create(['name' => 'Operasional', 'is_active' => true]);
        $otherItem = ExpenseItem::query()->create(['expense_category_id' => $otherCat->id, 'name' => 'Kuota', 'is_active' => true]);

        $this->actingAs($admin)->post(route('expense-commitments.store'), [
            'expense_item_id' => $item->id,
            'name' => 'CapCut Pro 1 Tahun',
            'total_amount' => 1200,
            'months' => 12,
            'start_date' => '2026-01-15',
        ]);

        $row = Expense::query()->whereNotNull('amortization_group')->first();

        $this->actingAs($admin)->put(route('expenses.update', $row), [
            'expense_item_id' => $otherItem->id,
            'amount' => $row->amount,
            'expense_date' => $row->expense_date->toDateString(),
        ])->assertSessionHasErrors('expense_item_id');

        $this->assertSame($item->id, (int) $row->fresh()->expense_item_id);
    }
}
