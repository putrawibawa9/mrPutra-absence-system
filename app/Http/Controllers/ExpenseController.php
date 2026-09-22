<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            ->with(['category', 'creator', 'item'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim((string) $search);

                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('item', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"))
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
        return view('expenses.create', ['expenseItems' => $this->selectableItems()]);
    }

    public function store(ExpenseRequest $request)
    {
        $item = ExpenseItem::query()->with('category')->findOrFail($request->integer('expense_item_id'));

        // Warning (tidak memblokir): keyword judul mengarah ke item lain, atau
        // kemungkinan dobel untuk item kategori fixed dalam ±20 hari.
        if (! $request->boolean('confirmed')) {
            $warnings = $this->buildWarnings($item, $request->string('title')->toString(), $request->date('expense_date'));

            if ($warnings !== []) {
                return redirect()->route('expenses.create')
                    ->withInput()
                    ->with('expense_warnings', $warnings);
            }
        }

        Expense::create([
            // Kategori SELALU dari item, tidak pernah dari request.
            'expense_category_id' => $item->expense_category_id,
            'expense_item_id' => $item->id,
            'created_by_user_id' => $request->user()->id,
            'title' => $request->string('title')->toString() ?: $item->name,
            'amount' => $request->integer('amount'),
            'expense_date' => $request->date('expense_date'),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()->route('expenses.index')->with('status', 'Expense berhasil ditambahkan.');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', [
            'expense' => $expense->load('item.category'),
            'expenseItems' => $this->selectableItems(),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $item = ExpenseItem::query()->with('category')->findOrFail($request->integer('expense_item_id'));

        // Baris cicilan (amortization_group) tidak boleh diganti item/kategorinya
        // satu per satu — harus lewat komitmen untuk seluruh grup.
        if ($expense->amortization_group && (int) $expense->expense_item_id !== $item->id) {
            return back()->withInput()->withErrors([
                'expense_item_id' => 'Baris cicilan tidak bisa ganti item/kategori sendiri. Ubah lewat Komitmen (berlaku untuk seluruh grup).',
            ]);
        }

        $expense->update([
            'expense_category_id' => $item->expense_category_id,
            'expense_item_id' => $item->id,
            'title' => $request->string('title')->toString() ?: $item->name,
            'amount' => $request->integer('amount'),
            'expense_date' => $request->date('expense_date'),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()->route('expenses.index')->with('status', 'Expense berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Expense berhasil dihapus.');
    }

    /**
     * Item yang boleh dipilih di form (aktif & kategori non-sistem), plus kategori
     * ter-load untuk ditampilkan read-only mengikuti item.
     */
    protected function selectableItems()
    {
        return ExpenseItem::query()
            ->selectable()
            ->with('category:id,name,cost_behavior')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    protected function buildWarnings(ExpenseItem $item, string $title, Carbon $date): array
    {
        $warnings = [];
        $title = trim($title);

        // 1) Keyword item lain cocok dengan judul → mungkin salah pilih item.
        if ($title !== '') {
            $lowerTitle = mb_strtolower($title);
            $others = ExpenseItem::query()
                ->selectable()
                ->where('id', '!=', $item->id)
                ->get();

            foreach ($others as $other) {
                foreach ($other->keywordList() as $keyword) {
                    if ($keyword !== '' && str_contains($lowerTitle, $keyword)) {
                        $warnings[] = 'Judul "'.$title.'" mengandung kata "'.$keyword.'" yang biasanya milik item "'.$other->name.'". Pastikan item sudah benar.';
                        break 2;
                    }
                }
            }
        }

        // 2) Deteksi dobel: item kategori fixed sudah tercatat dalam ±20 hari.
        if ($item->category && $item->category->cost_behavior === ExpenseCategory::COST_FIXED) {
            $exists = Expense::query()
                ->where('expense_item_id', $item->id)
                ->whereDate('expense_date', '>=', $date->copy()->subDays(20)->toDateString())
                ->whereDate('expense_date', '<=', $date->copy()->addDays(20)->toDateString())
                ->exists();

            if ($exists) {
                $warnings[] = 'Item "'.$item->name.'" (biaya tetap) sudah tercatat dalam ±20 hari terakhir. Cek apakah ini pembayaran dobel.';
            }
        }

        return $warnings;
    }
}
