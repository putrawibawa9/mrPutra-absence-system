<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Expense Category</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('expense-categories.update', $expense_category) }}" data-confirm="Update this expense category?">
            @method('PUT')
            @include('expense-categories._form', [
                'submitLabel' => 'Update Category',
            ])
        </form>
    </div>
</x-app-layout>
