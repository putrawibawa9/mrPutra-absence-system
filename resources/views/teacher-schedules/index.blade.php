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

        @if (! empty($calendar['colors']))
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($calendar['colors'] as $tid => $hex)
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">
                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $hex }}"></span>
                        {{ optional($teachers->firstWhere('id', $tid))->name ?? 'Guru' }}
                    </span>
                @endforeach
            </div>
        @endif

        @if ($calendar['isEmpty'])
            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">Belum ada jadwal untuk ditampilkan di kalender.</p>
        @else
            <div class="overflow-x-auto">
                <div style="min-width: 760px;">
                    {{-- Header hari --}}
                    <div class="grid" style="grid-template-columns: 56px repeat(7, minmax(0, 1fr));">
                        <div></div>
                        @foreach ($calendar['days'] as $day)
                            <div class="border-b border-slate-100 pb-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $day['label'] }}</div>
                        @endforeach
                    </div>

                    {{-- Body grid --}}
                    <div class="grid" style="grid-template-columns: 56px repeat(7, minmax(0, 1fr));">
                        {{-- Gutter jam --}}
                        <div style="position: relative; height: {{ $calendar['gridHeight'] }}px;">
                            @foreach ($calendar['hours'] as $h)
                                <div class="text-[11px] text-slate-400" style="position: absolute; right: 6px; top: {{ ($h * 60 - $calendar['startMin']) / 60 * $calendar['hourHeight'] - 6 }}px;">{{ sprintf('%02d:00', $h) }}</div>
                            @endforeach
                        </div>

                        {{-- Kolom hari --}}
                        @foreach ($calendar['days'] as $day)
                            <div class="border-l border-slate-100" style="position: relative; height: {{ $calendar['gridHeight'] }}px;">
                                @foreach ($calendar['hours'] as $h)
                                    <div class="border-t border-slate-100" style="position: absolute; left: 0; right: 0; top: {{ ($h * 60 - $calendar['startMin']) / 60 * $calendar['hourHeight'] }}px;"></div>
                                @endforeach

                                @foreach ($day['events'] as $e)
                                    <a href="{{ route('teacher-schedules.edit', $e['s']) }}"
                                        title="{{ $e['s']->teacher->name }} — {{ $e['s']->timeRangeLabel() }}{{ $e['s']->title ? ' — '.$e['s']->title : '' }}{{ $e['s']->student ? ' ('.$e['s']->student->name.')' : '' }}"
                                        class="block overflow-hidden rounded-lg px-2 py-1 text-[11px] leading-tight text-white shadow-sm"
                                        style="position: absolute; top: {{ $e['top'] }}px; height: {{ $e['height'] }}px; left: calc({{ $e['left'] }}% + 2px); width: calc({{ $e['width'] }}% - 4px); background: {{ $e['color'] }};{{ $e['s']->is_active ? '' : ' opacity: 0.45;' }}">
                                        <span class="block truncate font-semibold">{{ $e['s']->timeRangeLabel() }}</span>
                                        <span class="block truncate">{{ $e['s']->teacher->name }}</span>
                                        @if ($e['s']->classroom)
                                            <span class="block truncate">{{ $e['s']->classroom->name }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($schedules as $schedule)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $schedule->teacher->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $schedule->dayLabel() }} | {{ $schedule->timeRangeLabel() }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $schedule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $schedule->statusLabel() }}
                    </span>
                </div>
                <div class="mt-4 space-y-1 text-sm text-slate-600">
                    <p>Kelas: {{ $schedule->classroom?->name ?: '-' }}</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('teacher-schedules.edit', $schedule) }}" class="text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('teacher-schedules.destroy', $schedule) }}" data-confirm="Hapus jadwal guru ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">Belum ada jadwal guru.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Guru</th>
                    <th class="px-6 py-3 font-medium">Hari</th>
                    <th class="px-6 py-3 font-medium">Jam</th>
                    <th class="px-6 py-3 font-medium">Kelas</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($schedules as $schedule)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $schedule->teacher->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $schedule->dayLabel() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $schedule->timeRangeLabel() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $schedule->classroom?->name ?: '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $schedule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $schedule->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('teacher-schedules.edit', $schedule) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('teacher-schedules.destroy', $schedule) }}" data-confirm="Hapus jadwal guru ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada jadwal guru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $schedules->links() }}
    </div>
</x-app-layout>
