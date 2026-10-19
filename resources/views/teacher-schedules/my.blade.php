<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Jadwal Saya</h2>
            <p class="text-sm text-slate-500">Jadwal mengajar Anda per tanggal — sudah termasuk kelas pengganti, pindahan, & pembatalan.</p>
        </div>
    </x-slot>

    {{-- Navigasi minggu --}}
    <div class="mb-5 flex flex-col gap-3 rounded-3xl bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('my-schedule.index', ['week' => $prevWeek]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">&larr;</a>
            <a href="{{ route('my-schedule.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Minggu ini</a>
            <a href="{{ route('my-schedule.index', ['week' => $nextWeek]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">&rarr;</a>
            <span class="ml-2 text-sm font-medium text-slate-700">{{ $weekStart->translatedFormat('d M') }} – {{ $weekEnd->translatedFormat('d M Y') }}</span>
        </div>
        <form method="GET" action="{{ route('my-schedule.index') }}" class="flex items-center gap-2">
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
                            @php($faded = in_array($o->status, ['moved_out', 'cancelled']))
                            <div class="rounded-xl border p-3 text-sm {{ $o->conflict ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50' }} {{ $faded ? 'opacity-60' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold {{ $o->conflict ? 'text-rose-700' : 'text-slate-900' }}">{{ $o->start }}–{{ $o->end }}</span>
                                    @if ($o->classroom?->code)
                                        <span class="rounded bg-slate-100 text-[11px] font-bold tracking-wide text-slate-700" style="padding:2px 6px;">{{ $o->classroom->code }}</span>
                                    @endif
                                </div>
                                @if ($o->conflict)
                                    <p class="mt-1 text-[11px] font-semibold text-rose-700">⚠ Bentrok jadwal</p>
                                @endif
                                <p class="mt-1 font-medium text-slate-800">{{ $o->classroom?->name ?? ($o->schedule->title ?: 'Kelas') }}</p>
                                @if ($o->students)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $o->students }}</p>
                                @endif

                                {{-- Peran / status --}}
                                @if ($o->my_role === 'substitute')
                                    <span class="mt-1 inline-block rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700" style="padding:2px 8px;">Menggantikan {{ $o->original_teacher?->name ?? '-' }}</span>
                                @elseif ($o->my_role === 'co_teacher')
                                    <span class="mt-1 inline-block rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700" style="padding:2px 8px;">Co-teacher (utama: {{ $o->effective_teacher?->name ?? '-' }})</span>
                                @endif

                                @if ($o->status === 'moved_in')
                                    <span class="mt-1 inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700" style="padding:2px 8px;">Pindahan</span>
                                @elseif ($o->status === 'retimed')
                                    <p class="mt-1 text-[11px] font-medium text-sky-700">{{ $o->note }}</p>
                                @elseif ($o->status === 'moved_out')
                                    <p class="mt-1 text-[11px] font-medium text-slate-500">{{ $o->note }}</p>
                                @elseif ($o->status === 'cancelled')
                                    <p class="mt-1 text-[11px] font-medium text-rose-700">{{ $o->note }}</p>
                                @elseif ($o->status === 'leave_unassigned')
                                    <p class="mt-1 text-[11px] font-medium text-rose-700">Anda libur — belum ada pengganti</p>
                                @endif
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
