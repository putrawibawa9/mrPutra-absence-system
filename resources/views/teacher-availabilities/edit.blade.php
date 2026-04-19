<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Ketersediaan Guru</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teacher-availabilities.update', $availability) }}" data-confirm="Update ketersediaan guru ini?">
            @csrf
            @method('PUT')
            @include('teacher-availabilities._form', ['cancelRoute' => route('teacher-availabilities.index')])
        </form>
    </div>
</x-app-layout>
