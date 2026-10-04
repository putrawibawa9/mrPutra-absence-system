<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Buku</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('books.store') }}">
            @include('books._form', ['submitLabel' => 'Simpan Buku'])
        </form>
    </div>
</x-app-layout>
