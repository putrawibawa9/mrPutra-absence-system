<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Jadwal Mingguan Saya</h2>
            <p class="text-sm text-slate-500">Jadwal mengajar mingguan Anda — sebagai guru utama maupun co-teacher.</p>
        </div>
    </x-slot>

    <div class="mb-6 rounded-3xl bg-white p-4 shadow-sm sm:p-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Kalender Mingguan</h3>
            <p class="text-sm text-slate-500">Jadwal berulang tiap minggu.</p>
        </div>
        @include('teacher-schedules._calendar', ['mode' => 'teacher'])
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($groupedSchedules as $day)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ $day->label }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($day->items as $schedule)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-slate-900">{{ $schedule->timeRangeLabel() }}</p>
                                @if (($schedule->my_role ?? 'teacher') === 'co_teacher')
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">Co-teacher</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Guru utama</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-600">Murid: {{ $schedule->classroom?->studentHint() ?: ($schedule->title ?: '-') }}</p>
                            @if (($schedule->my_role ?? '') === 'co_teacher')
                                <p class="text-xs text-slate-500">Guru utama: {{ $schedule->teacher?->name }}</p>
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
