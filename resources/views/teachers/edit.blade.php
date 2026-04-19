<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Teacher</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teachers.update', $teacher) }}" data-confirm="Update this teacher? Please double check the account data first.">
            @csrf
            @method('PUT')
            @include('teachers._form')
        </form>
    </div>
</x-app-layout>
