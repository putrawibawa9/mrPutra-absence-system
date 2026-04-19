<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Jadwal Guru</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teacher-schedules.update', $schedule) }}" data-confirm="Update jadwal guru ini?">
            @csrf
            @method('PUT')
            @include('teacher-schedules._form')
        </form>
    </div>
</x-app-layout>
