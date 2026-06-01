<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Expense</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('expenses.store') }}" data-confirm="Save this expense? Please double check the nominal and category first.">
            @include('expenses._form', [
                'submitLabel' => 'Save Expense',
            ])
        </form>
    </div>
</x-app-layout>
