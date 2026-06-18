<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Link Materi</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('material-links.store') }}" data-confirm="Simpan link materi ini?">
            @csrf
            @include('material-links._form')
        </form>
    </div>
</x-app-layout>
