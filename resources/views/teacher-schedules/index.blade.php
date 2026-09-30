<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Jadwal Guru</h2>
                <p class="text-sm text-slate-500">Kelola jadwal mengajar mingguan untuk setiap guru.</p>
            </div>
            <a href="{{ route('teacher-schedules.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Tambah Jadwal</a>
        </div>
    </x-slot>

    {{-- ===== Filter (guru + kategori kelas) ===== --}}
    @php($hasFilter = $teacherId || $filters['division'] || $filters['format'] || $filters['age_group'])
    <div class="mb-6 rounded-3xl bg-white p-4 shadow-sm sm:p-6">
        <form method="GET" action="{{ route('teacher-schedules.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500">Guru</label>
                <select name="teacher_id" class="mt-1 rounded-xl border-slate-300 text-sm">
                    <option value="">Semua guru</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected($teacherId === $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Divisi</label>
                <select name="division" class="mt-1 rounded-xl border-slate-300 text-sm">
                    <option value="">Semua divisi</option>
                    @foreach ($divisionOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['division'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Format</label>
                <select name="format" class="mt-1 rounded-xl border-slate-300 text-sm">
                    <option value="">Semua format</option>
                    @foreach ($formatOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['format'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Umur</label>
                <select name="age_group" class="mt-1 rounded-xl border-slate-300 text-sm">
                    <option value="">Semua umur</option>
                    @foreach ($ageOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['age_group'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Terapkan</button>
                @if ($hasFilter)
                    <a href="{{ route('teacher-schedules.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- ===== Kalender mingguan (tampilan seperti Google Calendar) ===== --}}
    <div class="mb-6 rounded-3xl bg-white p-4 shadow-sm sm:p-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Kalender Mingguan</h3>
            <p class="text-sm text-slate-500">Jadwal berulang tiap minggu. Klik blok untuk mengubah.</p>
        </div>

        @include('teacher-schedules._calendar', ['mode' => 'admin'])
    </div>

    {{-- ===== Kartu per grup kelas (identitas = nama siswa) ===== --}}
    <div class="mb-2 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">Jadwal per Grup Kelas</h3>
        <span class="text-sm text-slate-500">{{ $classroomCards->count() }} grup</span>
    </div>

    {{-- Grid kartu kecil: auto-flow 1 kolom di HP, 3-4 kolom di laptop. --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:0.75rem;">
        @forelse ($classroomCards as $card)
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white">
                        {{ strtoupper(mb_substr($card->student_hint, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-slate-900">{{ $card->student_hint }}</h3>
                        <p class="truncate text-xs text-slate-500">
                            @if ($card->classroom?->code)<span class="font-semibold text-slate-600">{{ $card->classroom->code }}</span> · @endif{{ $card->slots->count() }} sesi/mgg
                        </p>
                        <p class="truncate text-xs text-slate-500">Guru: <span class="font-medium text-slate-700">{{ $card->teacher_names }}</span></p>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    @foreach ($card->slots as $slot)
                        <div class="flex items-center justify-between gap-1 rounded-xl border border-slate-100 bg-slate-50 px-2.5 py-1 text-xs {{ $slot->is_active ? '' : 'opacity-60' }}">
                            <div class="flex min-w-0 items-center gap-1.5">
                                <span class="shrink-0 rounded-md border border-slate-200 bg-white px-2 py-1 font-semibold text-slate-700">{{ mb_substr($slot->dayLabel(), 0, 3) }}</span>
                                <span class="truncate font-semibold text-slate-900">{{ $slot->timeRangeLabel() }}</span>
                            </div>
                            <div class="flex shrink-0 items-center gap-2 font-medium">
                                <a href="{{ route('teacher-schedules.edit', $slot) }}" class="text-slate-600 hover:text-slate-900">Edit</a>
                                <form method="POST" action="{{ route('teacher-schedules.destroy', $slot) }}" data-confirm="Hapus jadwal ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-700">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500" style="grid-column:1/-1;">Belum ada jadwal guru.</div>
        @endforelse
    </div>

    {{-- ===== Grup yang belum dijadwalkan ===== --}}
    <div class="mb-2 mt-8 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">Grup Belum Dijadwalkan</h3>
        <span class="text-sm {{ $unscheduledGroups->isEmpty() ? 'text-emerald-600' : 'text-amber-600' }}">{{ $unscheduledGroups->count() }} grup</span>
    </div>
    <p class="mb-3 text-sm text-slate-500">Kelas aktif yang belum punya jadwal mingguan aktif — perlu dibuatkan jadwal.</p>

    @if ($unscheduledGroups->isEmpty())
        <div class="rounded-3xl border border-dashed border-emerald-200 bg-emerald-50 px-6 py-8 text-center text-sm text-emerald-700">Semua grup aktif sudah punya jadwal. 🎉</div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:0.75rem;">
            @foreach ($unscheduledGroups as $group)
                <div class="rounded-2xl border border-amber-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700">
                            {{ strtoupper(mb_substr($group->studentHint(), 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-slate-900">{{ $group->studentHint() }}</h3>
                            <p class="truncate text-xs text-slate-500">
                                @if ($group->code)<span class="font-semibold text-slate-600">{{ $group->code }}</span> · @endif{{ $group->formatLabel() }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('teacher-schedules.create', ['classroom_id' => $group->id]) }}" class="mt-3 inline-flex w-full justify-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white">+ Buat Jadwal</a>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
