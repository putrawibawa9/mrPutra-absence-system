<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Expense Category</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('expense-categories.store') }}" data-confirm="Save this expense category?">
            @include('expense-categories._form', [
                'submitLabel' => 'Save Category',
            ])
        </form>
    </div>
</x-app-layout>
