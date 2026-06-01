<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_expenses_by_search_category_date_and_amount(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Finance Admin']);
        $operationalCategory = ExpenseCategory::query()->create([
            'name' => 'Operasional',
            'is_active' => true,
        ]);
        $marketingCategory = ExpenseCategory::query()->create([
            'name' => 'Marketing',
            'is_active' => true,
        ]);

        Expense::query()->create([
            'expense_category_id' => $operationalCategory->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Printer Ink',
            'amount' => 125000,
            'expense_date' => '2026-05-10',
            'notes' => 'Black cartridge refill',
        ]);
        Expense::query()->create([
            'expense_category_id' => $marketingCategory->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Instagram Ads',
            'amount' => 300000,
            'expense_date' => '2026-05-12',
            'notes' => 'Enrollment campaign',
        ]);
        Expense::query()->create([
            'expense_category_id' => $operationalCategory->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Office Snacks',
            'amount' => 75000,
            'expense_date' => '2026-04-28',
            'notes' => 'Pantry',
        ]);

        $response = $this->actingAs($admin)->get(route('expenses.index', [
            'search' => 'printer',
            'expense_category_id' => $operationalCategory->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
            'amount_min' => 100000,
            'amount_max' => 200000,
        ]));

        $response->assertOk();
        $response->assertSee('Printer Ink');
        $response->assertDontSee('Instagram Ads');
        $response->assertDontSee('Office Snacks');
    }
}
