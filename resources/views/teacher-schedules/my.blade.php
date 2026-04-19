<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Jadwal Mingguan Saya</h2>
            <p class="text-sm text-slate-500">Lihat jadwal mengajar mingguan Anda yang sudah ditetapkan admin.</p>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($groupedSchedules as $day)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ $day->label }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($day->items as $schedule)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">{{ $schedule->timeRangeLabel() }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $schedule->title ?: 'Tanpa info kelas khusus' }}</p>
                            <p class="mt-1 text-sm text-slate-500">Murid: {{ $schedule->student?->name ?: '-' }}</p>
                            @if ($schedule->notes)
                                <p class="mt-2 text-sm text-slate-500">{{ $schedule->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada jadwal.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
