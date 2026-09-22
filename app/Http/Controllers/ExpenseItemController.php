<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseItemRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;

class ExpenseItemController extends Controller
{
    public function index()
    {
        $expenseItems = ExpenseItem::query()
            ->with('category:id,name')
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(20);

        return view('expense-items.index', compact('expenseItems'));
    }

    public function create()
    {
        return view('expense-items.create', ['categories' => $this->selectableCategories()]);
    }

    public function store(ExpenseItemRequest $request)
    {
        ExpenseItem::create([
            'expense_category_id' => $request->integer('expense_category_id'),
            'name' => $request->string('name')->toString(),
            'keywords' => $request->string('keywords')->toString() ?: null,
            'default_amount' => $request->filled('default_amount') ? $request->integer('default_amount') : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('expense-items.index')->with('status', 'Item expense berhasil ditambahkan.');
    }

    public function edit(ExpenseItem $expense_item)
    {
        return view('expense-items.edit', [
            'expense_item' => $expense_item,
            'categories' => $this->selectableCategories(),
        ]);
    }

    public function update(ExpenseItemRequest $request, ExpenseItem $expense_item)
    {
        $expense_item->update([
            'expense_category_id' => $request->integer('expense_category_id'),
            'name' => $request->string('name')->toString(),
            'keywords' => $request->string('keywords')->toString() ?: null,
            'default_amount' => $request->filled('default_amount') ? $request->integer('default_amount') : null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('expense-items.index')->with('status', 'Item expense berhasil diperbarui.');
    }

    public function toggleStatus(ExpenseItem $expense_item)
    {
        $expense_item->update(['is_active' => ! $expense_item->is_active]);

        return back()->with('status', $expense_item->is_active ? 'Item diaktifkan.' : 'Item dinonaktifkan.');
    }

    public function destroy(ExpenseItem $expense_item)
    {
        // FK RESTRICT: item yang sudah dipakai expense tidak boleh dihapus.
        if ($expense_item->expenses()->exists()) {
            return back()->with('status', 'Item sudah dipakai di expense — tidak bisa dihapus. Nonaktifkan saja.');
        }

        $expense_item->delete();

        return redirect()->route('expense-items.index')->with('status', 'Item expense dihapus.');
    }

    protected function selectableCategories()
    {
        return ExpenseCategory::query()->selectable()->orderBy('name')->get();
    }
}
