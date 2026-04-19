<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Jadwal Guru</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teacher-schedules.store') }}" data-confirm="Simpan jadwal guru ini?">
            @csrf
            @include('teacher-schedules._form')
        </form>
    </div>
</x-app-layout>
