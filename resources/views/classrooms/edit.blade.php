<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Kelas</h2>
            <p class="text-sm text-slate-500">Ubah anggota kapan saja — riwayat absensi yang lama tidak terpengaruh.</p>
        </div>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        @include('classrooms._form', [
            'action' => route('classrooms.update', $classroom),
            'method' => 'PUT',
            'submitLabel' => 'Simpan Perubahan',
        ])
    </div>
</x-app-layout>
