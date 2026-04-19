<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Ketersediaan Guru</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teacher-availabilities.store') }}" data-confirm="Simpan ketersediaan guru ini?">
            @csrf
            @include('teacher-availabilities._form', ['cancelRoute' => route('teacher-availabilities.index')])
        </form>
    </div>
</x-app-layout>
