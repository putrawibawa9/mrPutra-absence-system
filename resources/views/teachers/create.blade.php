<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Teacher</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teachers.store') }}" data-confirm="Save this teacher? Please double check the account data first.">
            @csrf
            @include('teachers._form')
        </form>
    </div>
</x-app-layout>
