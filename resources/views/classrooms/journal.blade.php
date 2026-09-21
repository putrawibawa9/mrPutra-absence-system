<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <a href="{{ route('classrooms.index') }}" class="text-sm text-slate-500">&larr; Kembali ke Kelas</a>
            <div class="flex flex-wrap items-center gap-2">
                @if ($classroom->code)
                    <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-bold tracking-wide text-white">{{ $classroom->code }}</span>
                @endif
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Jurnal Kelas — {{ $classroom->name }}</h2>
            </div>
            <p class="text-sm text-slate-500">{{ $classroom->divisionLabel() }} · {{ $classroom->formatLabel() }} · {{ $classroom->ageLabel() }} — {{ $entries->count() }} sesi tercatat.</p>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if ($classroom->is_active)
            <div>
                <a href="{{ route('classrooms.attendances.create', $classroom) }}" class="inline-flex justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white">+ Absen / Jurnal Baru</a>
            </div>
        @endif

        @forelse ($entries as $entry)
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-1 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-slate-900">{{ $entry->date?->locale('id')->translatedFormat('l, d F Y') ?? '-' }}</p>
                    <p class="text-sm text-slate-500">Guru: <span class="font-medium text-slate-700">{{ $entry->teachers }}</span></p>
                </div>
                @if ($entry->students)
                    <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-400">Murid</p>
                    <p class="text-sm text-slate-600">{{ $entry->students }}</p>
                @endif
                <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $entry->journal }}</p>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Belum ada jurnal untuk kelas ini.
            </div>
        @endforelse
    </div>
</x-app-layout>
