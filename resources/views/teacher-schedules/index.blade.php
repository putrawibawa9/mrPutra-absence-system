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

    {{-- ===== Kalender mingguan (tampilan seperti Google Calendar) ===== --}}
    <div class="mb-6 rounded-3xl bg-white p-4 shadow-sm sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Kalender Mingguan</h3>
                <p class="text-sm text-slate-500">Jadwal berulang tiap minggu. Klik blok untuk mengubah.</p>
            </div>
            <form method="GET" action="{{ route('teacher-schedules.index') }}">
                <select name="teacher_id" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm">
                    <option value="">Semua guru</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected($teacherId === $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </form>
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
</x-app-layout>
