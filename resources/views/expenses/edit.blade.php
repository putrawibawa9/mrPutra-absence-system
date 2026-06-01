<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Expense</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('expenses.update', $expense) }}" data-confirm="Update this expense? Please double check the nominal and category first.">
            @method('PUT')
            @include('expenses._form', [
                'submitLabel' => 'Update Expense',
            ])
        </form>
    </div>
</x-app-layout>
