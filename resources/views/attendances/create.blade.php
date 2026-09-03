<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Catat Absensi — Pilih Kelas</h2>
                <p class="text-sm text-slate-500">Cari kelas, lalu absen langsung centang murid yang hadir.</p>
            </div>
            <a href="{{ route('attendances.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Riwayat Absensi</a>
        </div>
    </x-slot>

    <div class="space-y-4" x-data="{ q: '' }">
        <div class="rounded-3xl bg-white p-5 shadow-sm">
            <input type="text" x-model="q" placeholder="Cari nama kelas / divisi / murid..." class="block w-full rounded-xl border-slate-300">
            <p class="mt-2 text-xs text-slate-400">Ketik nama murid pun bisa — yang muncul tetap kelasnya.</p>
        </div>

        @forelse ($classrooms as $classroom)
            @php($search = Str::lower($classroom->name.' '.$classroom->divisionLabel().' '.$classroom->formatLabel().' '.$classroom->ageLabel().' '.$classroom->students->pluck('name')->join(' ')))
            <div class="rounded-3xl bg-white p-5 shadow-sm"
                data-search="{{ $search }}"
                x-show="q.trim() === '' || $el.dataset.search.includes(q.toLowerCase().trim())">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $classroom->name }}</h3>
                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $classroom->divisionLabel() }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $classroom->isPrivate() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ $classroom->formatLabel() }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $classroom->ageLabel() }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $classroom->students_count }} murid</span>
                        </div>
                        @if ($classroom->students->isNotEmpty())
                            <p class="mt-2 truncate text-xs text-slate-400">{{ $classroom->students->pluck('name')->join(', ') }}</p>
                        @endif
                    </div>
                    <a href="{{ route('classrooms.attendances.create', $classroom) }}" class="inline-flex shrink-0 justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white">Absen Kelas</a>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Belum ada kelas aktif.
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('classrooms.create') }}" class="font-medium text-slate-900 underline">Buat kelas dulu</a>.
                @endif
            </div>
        @endforelse
    </div>
</x-app-layout>
