<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Expenses</h2>
                <p class="text-sm text-slate-500">Catat semua pengeluaran operasional agar net profit dan margin terlihat jelas.</p>
            </div>
            <a href="{{ route('expenses.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Expense</a>
        </div>
    </x-slot>

    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('expenses.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="md:col-span-2 xl:col-span-2">
                <x-input-label for="search" value="Search Expense" />
                <x-text-input
                    id="search"
                    name="search"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-slate-300"
                    :value="$filters['search'] ?? null"
                    placeholder="Search title, notes, category, or creator"
                />
            </div>

            <div>
                <x-input-label for="expense_category_id" value="Category" />
                <select id="expense_category_id" name="expense_category_id" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">All categories</option>
                    @foreach ($expenseCategories as $expenseCategory)
                        <option value="{{ $expenseCategory->id }}" @selected((string) ($filters['expense_category_id'] ?? '') === (string) $expenseCategory->id)>
                            {{ $expenseCategory->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="date_from" value="Date From" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_from'] ?? null" />
            </div>

            <div>
                <x-input-label for="date_to" value="Date To" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_to'] ?? null" />
            </div>

            <div>
                <x-input-label for="amount_min" value="Min Amount" />
                <x-text-input id="amount_min" name="amount_min" type="number" min="0" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['amount_min'] ?? null" placeholder="0" />
            </div>

            <div>
                <x-input-label for="amount_max" value="Max Amount" />
                <x-text-input id="amount_max" name="amount_max" type="number" min="0" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['amount_max'] ?? null" placeholder="0" />
            </div>

            <div class="flex flex-col gap-3 md:col-span-2 md:flex-row md:items-end xl:col-span-2">
                <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white md:w-auto">
                    Filter
                </button>
                <a href="{{ route('expenses.index') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 md:w-auto">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($expenses as $expense)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $expense->title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $expense->category->name }}</p>
                    </div>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
                </div>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Date</dt>
                        <dd class="font-medium text-slate-900">{{ $expense->expense_date->format('d M Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Created by</dt>
                        <dd class="font-medium text-slate-900">{{ $expense->creator?->name ?? '-' }}</dd>
                    </div>
                    @if ($expense->notes)
                        <div>
                            <dt class="text-slate-500">Notes</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $expense->notes }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('expenses.edit', $expense) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm="Delete this expense record?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No expense matched the current filter.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Expense</th>
                    <th class="px-6 py-3 font-medium">Category</th>
                    <th class="px-6 py-3 font-medium">Amount</th>
                    <th class="px-6 py-3 font-medium">Date</th>
                    <th class="px-6 py-3 font-medium">Created By</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $expense->title }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $expense->category->name }}</td>
                        <td class="px-6 py-4 font-medium text-rose-700">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $expense->expense_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $expense->creator?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('expenses.edit', $expense) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm="Delete this expense record?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No expense matched the current filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</x-app-layout>
