<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Buat Kelas</h2>
            <p class="text-sm text-slate-500">Tentukan tipe, lalu pilih murid yang masuk kelas ini.</p>
        </div>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        @include('classrooms._form', [
            'action' => route('classrooms.store'),
            'method' => 'POST',
            'submitLabel' => 'Buat Kelas',
            'selectedStudentIds' => [],
        ])
    </div>
</x-app-layout>
