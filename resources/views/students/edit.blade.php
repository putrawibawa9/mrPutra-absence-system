<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-slate-900">Edit Student</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('students.update', $student) }}">
            @csrf
            @method('PUT')
            @include('students._form')
        </form>
    </div>
</x-app-layout>
