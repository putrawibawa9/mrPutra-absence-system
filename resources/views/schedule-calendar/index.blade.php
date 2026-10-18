<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Jadwal Guru</h2>
                <p class="text-sm text-slate-500">Kalender per tanggal. Atur guru pengganti / pindah sesi untuk tanggal tertentu di sini.</p>
            </div>
            <a href="{{ route('teacher-schedules.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Kelola Jadwal Dasar</a>
        </div>
    </x-slot>

    @php
        $statusMeta = [
            'substitute' => ['bg-amber-50', 'border-amber-200'],
            'leave_unassigned' => ['bg-rose-50', 'border-rose-200'],
            'moved_in' => ['bg-emerald-50', 'border-emerald-200'],
            'moved_out' => ['bg-slate-50', 'border-slate-200'],
            'cancelled' => ['bg-slate-50', 'border-slate-200'],
            'normal' => ['bg-white', 'border-slate-200'],
        ];
    @endphp

    @if (session('status'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    {{-- Navigasi minggu --}}
    <div class="mb-5 flex flex-col gap-3 rounded-3xl bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('schedule-calendar.index', ['week' => $prevWeek]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">&larr;</a>
            <a href="{{ route('schedule-calendar.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Minggu ini</a>
            <a href="{{ route('schedule-calendar.index', ['week' => $nextWeek]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">&rarr;</a>
            <span class="ml-2 text-sm font-medium text-slate-700">{{ $weekStart->translatedFormat('d M') }} – {{ $weekEnd->translatedFormat('d M Y') }}</span>
        </div>
        <form method="GET" action="{{ route('schedule-calendar.index') }}" class="flex items-center gap-2">
            <label class="text-xs text-slate-500">Lompat ke tanggal</label>
            <input type="date" name="week" value="{{ $weekStart->toDateString() }}" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm">
        </form>
    </div>

    {{-- Grid mingguan (scroll ke samping di layar sempit) --}}
    <div class="overflow-x-auto pb-2">
        <div style="display:grid;grid-template-columns:repeat(7,minmax(190px,1fr));gap:0.75rem;">
            @foreach ($days as $day)
                <div class="rounded-2xl {{ $day->is_today ? 'bg-slate-900' : 'bg-white' }} p-3 shadow-sm">
                    <div class="mb-2 border-b {{ $day->is_today ? 'border-white/20' : 'border-slate-100' }} pb-2">
                        <p class="text-xs font-semibold uppercase tracking-wide {{ $day->is_today ? 'text-slate-300' : 'text-slate-400' }}">{{ $day->label }}</p>
                        <p class="text-sm font-semibold {{ $day->is_today ? 'text-white' : 'text-slate-900' }}">{{ $day->date->translatedFormat('d M') }}</p>
                    </div>

                    <div class="space-y-2">
                        @forelse ($day->occurrences as $o)
                            @php
                                [$bg, $border] = $statusMeta[$o->status] ?? $statusMeta['normal'];
                                $faded = in_array($o->status, ['moved_out', 'cancelled']);
                                $anchorDate = ($o->status === 'moved_in' && $o->change) ? $o->change->date->toDateString() : $o->date->toDateString();
                            @endphp
                            <div class="rounded-xl border {{ $border }} {{ $bg }} p-3 text-sm {{ $faded ? 'opacity-60' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-900">{{ $o->start }}–{{ $o->end }}</span>
                                    @if ($o->classroom?->code)
                                        <span class="rounded bg-slate-100 text-[11px] font-bold tracking-wide text-slate-700" style="padding:2px 6px;">{{ $o->classroom->code }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 font-medium text-slate-800">{{ $o->classroom?->name ?? ($o->schedule->title ?: 'Kelas') }}</p>
                                @if ($o->students)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $o->students }}</p>
                                @endif

                                {{-- Guru --}}
                                @if ($o->status === 'substitute')
                                    <p class="mt-1 text-xs font-medium text-amber-700">Pengganti: {{ $o->effective_teacher?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">gantikan {{ $o->original_teacher?->name ?? '-' }}</p>
                                @elseif ($o->status === 'leave_unassigned')
                                    <p class="mt-1 text-xs font-medium text-rose-700">{{ $o->note }}</p>
                                    <p class="text-xs text-slate-500">Guru: {{ $o->effective_teacher?->name ?? '-' }}</p>
                                @else
                                    <p class="mt-1 text-xs text-slate-600">Guru: <span class="font-medium text-slate-700">{{ $o->effective_teacher?->name ?? 'Belum ada guru' }}</span></p>
                                @endif
                                @if ($o->note && ! in_array($o->status, ['leave_unassigned']))
                                    <p class="mt-1 text-xs font-medium {{ $o->status === 'cancelled' ? 'text-rose-700' : 'text-slate-500' }}">{{ $o->note }}</p>
                                @endif

                                {{-- Aksi admin --}}
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs font-medium text-emerald-700">Kelola</summary>
                                    <div class="mt-2 space-y-2">
                                        @if ($o->status !== 'moved_in')
                                            <form method="POST" action="{{ route('schedule-changes.substitute') }}" class="space-y-1">
                                                @csrf
                                                <input type="hidden" name="teacher_schedule_id" value="{{ $o->schedule->id }}">
                                                <input type="hidden" name="date" value="{{ $anchorDate }}">
                                                <select name="substitute_teacher_id" required class="block w-full rounded-lg border-slate-300 text-xs">
                                                    <option value="">Pilih guru pengganti…</option>
                                                    @foreach ($teachers as $t)
                                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="w-full rounded-lg px-2 py-1 text-xs font-medium text-white" style="background-color:#d97706;">Ganti guru (tgl ini)</button>
                                            </form>

                                            <form method="POST" action="{{ route('schedule-changes.move') }}" class="space-y-1" data-confirm="Pindahkan sesi ini ke tanggal yang dipilih?">
                                                @csrf
                                                <input type="hidden" name="teacher_schedule_id" value="{{ $o->schedule->id }}">
                                                <input type="hidden" name="date" value="{{ $anchorDate }}">
                                                <input type="date" name="moved_to_date" required class="block w-full rounded-lg border-slate-300 text-xs">
                                                <button class="w-full rounded-lg bg-slate-900 px-2 py-1 text-xs font-medium text-white">Pindah ke tgl lain</button>
                                            </form>

                                            <form method="POST" action="{{ route('schedule-changes.cancel') }}" data-confirm="Batalkan sesi pada tanggal ini?">
                                                @csrf
                                                <input type="hidden" name="teacher_schedule_id" value="{{ $o->schedule->id }}">
                                                <input type="hidden" name="date" value="{{ $anchorDate }}">
                                                <button class="w-full rounded-lg border border-rose-200 px-2 py-1 text-xs font-medium text-rose-600">Batalkan sesi</button>
                                            </form>
                                        @endif

                                        @if ($o->change)
                                            <form method="POST" action="{{ route('schedule-changes.reset') }}" data-confirm="Kembalikan sesi ini ke jadwal normal?">
                                                @csrf
                                                <input type="hidden" name="teacher_schedule_id" value="{{ $o->schedule->id }}">
                                                <input type="hidden" name="date" value="{{ $anchorDate }}">
                                                <button class="w-full rounded-lg border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600">Kembalikan normal</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-200 px-2 py-4 text-center text-xs text-slate-400">—</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
