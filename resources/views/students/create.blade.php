<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-slate-900">Add Student</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('students.store') }}" data-confirm="Save this student? Please double check the data first.">
            @csrf
            @include('students._form')
        </form>
    </div>
</x-app-layout>
