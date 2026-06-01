<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

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
        Expense::create([
            'expense_category_id' => $request->integer('expense_category_id'),
            'created_by_user_id' => $request->user()->id,
            'title' => $request->string('title')->toString(),
            'amount' => $request->integer('amount'),
            'expense_date' => $request->date('expense_date'),
            'notes' => $request->string('notes')->toString(),
        ]);

        return redirect()->route('expenses.index')->with('status', 'Expense berhasil ditambahkan.');
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
