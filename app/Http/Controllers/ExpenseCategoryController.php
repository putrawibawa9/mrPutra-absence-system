<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $expenseCategories = ExpenseCategory::query()
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(10);

        return view('expense-categories.index', compact('expenseCategories'));
    }

    public function create()
    {
        return view('expense-categories.create');
    }

    public function store(ExpenseCategoryRequest $request)
    {
        ExpenseCategory::create([
            'name' => $request->string('name')->toString(),
            'notes' => $request->string('notes')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('expense-categories.index')->with('status', 'Kategori expense berhasil ditambahkan.');
    }

    public function edit(ExpenseCategory $expense_category)
    {
        return view('expense-categories.edit', compact('expense_category'));
    }

    public function update(ExpenseCategoryRequest $request, ExpenseCategory $expense_category)
    {
        $expense_category->update([
            'name' => $request->string('name')->toString(),
            'notes' => $request->string('notes')->toString(),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('expense-categories.index')->with('status', 'Kategori expense berhasil diperbarui.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        $expense_category->delete();

        return redirect()->route('expense-categories.index')->with('status', 'Kategori expense berhasil dihapus.');
    }
}
