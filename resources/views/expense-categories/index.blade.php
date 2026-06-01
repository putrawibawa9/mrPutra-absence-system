<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Expense Categories</h2>
                <p class="text-sm text-slate-500">Kelompokkan expense agar cash flow lebih mudah dianalisis.</p>
            </div>
            <a href="{{ route('expense-categories.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Category</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($expenseCategories as $expenseCategory)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $expenseCategory->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $expenseCategory->expenses_count }} expense(s)</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $expenseCategory->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $expenseCategory->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if ($expenseCategory->notes)
                    <p class="mt-4 text-sm text-slate-600">{{ $expenseCategory->notes }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('expense-categories.edit', $expenseCategory) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('expense-categories.destroy', $expenseCategory) }}" data-confirm="Delete this expense category? Existing expenses in this category will also be removed by the database relation.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No expense category added yet.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Category</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Expenses</th>
                    <th class="px-6 py-3 font-medium">Notes</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($expenseCategories as $expenseCategory)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $expenseCategory->name }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $expenseCategory->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $expenseCategory->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $expenseCategory->expenses_count }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $expenseCategory->notes ?: '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('expense-categories.edit', $expenseCategory) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('expense-categories.destroy', $expenseCategory) }}" data-confirm="Delete this expense category? Existing expenses in this category will also be removed by the database relation.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No expense category added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $expenseCategories->links() }}
    </div>
</x-app-layout>
