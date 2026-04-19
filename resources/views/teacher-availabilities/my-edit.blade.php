<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Ketersediaan Saya</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('my-availability.update', $availability) }}" data-confirm="Update slot ketersediaan ini?">
            @csrf
            @method('PUT')
            @include('teacher-availabilities._form', ['cancelRoute' => route('my-availability.index'), 'isSelfForm' => true])
        </form>
    </div>
</x-app-layout>
