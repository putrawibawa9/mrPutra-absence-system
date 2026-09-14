<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'amount_min' => ['nullable', 'integer', 'min:0'],
            'amount_max' => ['nullable', 'integer', 'min:0', 'gte:amount_min'],
        ]);

        $expenses = Expense::query()
            ->with(['category', 'creator'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim((string) $search);

                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('creator', fn ($creatorQuery) => $creatorQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['expense_category_id'] ?? null, fn ($query, $categoryId) => $query->where('expense_category_id', $categoryId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('expense_date', '<=', $date))
            ->when($filters['amount_min'] ?? null, fn ($query, $amount) => $query->where('amount', '>=', $amount))
            ->when($filters['amount_max'] ?? null, fn ($query, $amount) => $query->where('amount', '<=', $amount))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $expenseCategories = ExpenseCategory::query()->orderBy('name')->get();

        return view('expenses.index', compact('expenses', 'expenseCategories', 'filters'));
    }

    public function create()
    {
        $expenseCategories = ExpenseCategory::query()->active()->orderBy('name')->get();

        return view('expenses.create', compact('expenseCategories'));
    }

    public function store(ExpenseRequest $request)
    {
        $months = max(1, (int) $request->integer('installment_months'));
        $total = $request->integer('amount');
        $date = $request->date('expense_date');

        $base = [
            'expense_category_id' => $request->integer('expense_category_id'),
            'created_by_user_id' => $request->user()->id,
            'title' => $request->string('title')->toString(),
            'notes' => $request->string('notes')->toString(),
        ];

        if ($months <= 1) {
            Expense::create(array_merge($base, [
                'amount' => $total,
                'expense_date' => $date,
            ]));

            return redirect()->route('expenses.index')->with('status', 'Expense berhasil ditambahkan.');
        }

        // Cicilan: pecah total jadi porsi bulanan (sisa pembagian ditaruh di
        // bulan-bulan awal supaya jumlahnya tetap sama persis dengan total).
        $per = intdiv($total, $months);
        $remainder = $total % $months;
        $group = (string) Str::uuid();

        DB::transaction(function () use ($base, $months, $per, $remainder, $date, $group): void {
            for ($i = 0; $i < $months; $i++) {
                Expense::create(array_merge($base, [
                    'title' => $base['title'].' (Cicilan '.($i + 1).'/'.$months.')',
                    'amount' => $per + ($i < $remainder ? 1 : 0),
                    'expense_date' => $date->copy()->addMonthsNoOverflow($i),
                    'amortization_group' => $group,
                    'amortization_index' => $i + 1,
                    'amortization_total' => $months,
                ]));
            }
        });

        return redirect()->route('expenses.index')
            ->with('status', 'Expense dibagi jadi '.$months.' cicilan bulanan.');
    }

    public function edit(Expense $expense)
    {
        $expenseCategories = ExpenseCategory::query()->active()->orderBy('name')->get();

        return view('expenses.edit', compact('expense', 'expenseCategories'));
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $expense->update([
            'expense_category_id' => $request->integer('expense_category_id'),
            'title' => $request->string('title')->toString(),
            'amount' => $request->integer('amount'),
            'expense_date' => $request->date('expense_date'),
            'notes' => $request->string('notes')->toString(),
        ]);

        return redirect()->route('expenses.index')->with('status', 'Expense berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Expense berhasil dihapus.');
    }
}
