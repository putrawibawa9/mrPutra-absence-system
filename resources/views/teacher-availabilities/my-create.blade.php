<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Ketersediaan Saya</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('my-availability.store') }}" data-confirm="Simpan slot ketersediaan ini?">
            @csrf
            @include('teacher-availabilities._form', ['cancelRoute' => route('my-availability.index'), 'isSelfForm' => true])
        </form>
    </div>
</x-app-layout>
