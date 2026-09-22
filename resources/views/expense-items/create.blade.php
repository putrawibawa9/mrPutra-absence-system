<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Item Expense</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('expense-items.store') }}">
            @include('expense-items._form', ['submitLabel' => 'Simpan Item'])
        </form>
    </div>
</x-app-layout>
