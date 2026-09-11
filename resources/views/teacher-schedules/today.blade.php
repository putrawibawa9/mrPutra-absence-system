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
        @forelse ($schedules as $schedule)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-sm font-bold tracking-wide text-white">{{ $schedule->timeRangeLabel() }}</span>
                            @if ($schedule->classroom?->code)
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold tracking-wide text-slate-700">{{ $schedule->classroom->code }}</span>
                            @endif
                            <span class="font-semibold text-slate-900">{{ $schedule->classroom?->name ?? ($schedule->title ?: 'Kelas') }}</span>
                            @if ($schedule->classroom)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $schedule->classroom->isPrivate() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ $schedule->classroom->isPrivate() ? 'Private' : 'Grup' }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-slate-500">Guru: <span class="font-medium text-slate-700">{{ $schedule->teacher->name }}</span></p>
                    </div>
                </div>

                @if ($schedule->classroom && $schedule->classroom->students->isNotEmpty())
                    <div class="mt-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Murid ({{ $schedule->classroom->students->count() }})</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $schedule->classroom->students->pluck('name')->join(', ') }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-10 text-center text-sm text-slate-500 shadow-sm">Tidak ada kelas terjadwal hari ini.</div>
        @endforelse
    </div>
</x-app-layout>
