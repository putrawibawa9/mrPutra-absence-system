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
