<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Buku</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('books.update', $book) }}">
            @method('PUT')
            @include('books._form', ['submitLabel' => 'Perbarui Buku'])
        </form>
    </div>
</x-app-layout>
