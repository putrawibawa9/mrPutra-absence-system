<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Kelas Hari Ini</h2>
            <p class="text-sm text-slate-500">{{ $dayLabel }}, {{ now()->locale('id')->translatedFormat('d F Y') }} — daftar kelas yang les hari ini. Ingatkan grupnya lewat WA secara manual.</p>
        </div>
    </x-slot>

    <div class="mb-4 flex items-center justify-between">
        <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">{{ $schedules->count() }} kelas</span>
    </div>

    <div class="space-y-4">
        @forelse ($schedules as $o)
            <div class="rounded-3xl p-5 shadow-sm {{ $o->conflict ? 'border border-rose-200 bg-rose-50' : 'bg-white' }} {{ $o->status === 'cancelled' ? 'opacity-60' : '' }}">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-sm font-bold tracking-wide text-white">{{ $o->start }}–{{ $o->end }}</span>
                            @if ($o->classroom?->code)
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold tracking-wide text-slate-700">{{ $o->classroom->code }}</span>
                            @endif
                            <span class="font-semibold {{ $o->conflict ? 'text-rose-700' : 'text-slate-900' }}">{{ $o->classroom?->name ?? ($o->schedule->title ?: 'Kelas') }}</span>
                            @if ($o->classroom)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $o->classroom->isPrivate() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ $o->classroom->isPrivate() ? 'Private' : 'Grup' }}</span>
                            @endif
                            @if ($o->conflict)
                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">⚠ Bentrok guru</span>
                            @endif
                            @if ($o->status === 'moved_in')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Pindahan</span>
                            @elseif ($o->status === 'retimed')
                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">Jam diubah</span>
                            @elseif ($o->status === 'cancelled')
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Dibatalkan</span>
                            @endif
                        </div>
                        @if ($o->conflict)
                            <p class="mt-2 text-sm font-semibold text-rose-700">Guru: {{ $o->effective_teacher?->name ?? '-' }} — bentrok jadwal</p>
                        @elseif ($o->status === 'substitute')
                            <p class="mt-2 text-sm text-amber-700">Guru: <span class="font-medium">{{ $o->effective_teacher?->name ?? '-' }}</span> <span class="text-xs text-slate-500">(gantikan {{ $o->original_teacher?->name ?? '-' }})</span></p>
                        @elseif ($o->status === 'leave_unassigned')
                            <p class="mt-2 text-sm text-rose-700">{{ $o->effective_teacher?->name ?? '-' }} — guru libur, belum ada pengganti</p>
                        @else
                            <p class="mt-2 text-sm text-slate-500">Guru: <span class="font-medium text-slate-700">{{ $o->effective_teacher?->name ?? 'Belum ada guru' }}</span></p>
                        @endif
                    </div>
                </div>

                @if ($o->students)
                    <div class="mt-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Murid</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $o->students }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-10 text-center text-sm text-slate-500 shadow-sm">Tidak ada kelas terjadwal hari ini.</div>
        @endforelse
    </div>
</x-app-layout>
