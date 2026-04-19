<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500">Quick overview of new registrations, students who left this month, and active students.</p>
            </div>
            @if (auth()->user()->isTeacher())
                <a href="{{ route('attendances.create') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    Add Attendance
                </a>
            @endif
        </div>
    </x-slot>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Pendaftaran baru bulan ini</p>
            <p class="mt-3 text-4xl font-semibold text-slate-900">{{ $newRegistrationsThisMonth }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Siswa keluar bulan ini</p>
            <p class="mt-3 text-4xl font-semibold text-amber-600">{{ $studentsExitedThisMonth }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Murid yang aktif</p>
            <p class="mt-3 text-4xl font-semibold text-emerald-600">{{ $activeStudents }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total siswa coding</p>
            <p class="mt-3 text-4xl font-semibold text-sky-600">{{ $codingStudents }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total siswa english</p>
            <p class="mt-3 text-4xl font-semibold text-indigo-600">{{ $englishStudents }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-3xl bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Students with 0 sessions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Student</th>
                        <th class="px-6 py-3 font-medium">Phone</th>
                        <th class="px-6 py-3 font-medium">Email</th>
                        <th class="px-6 py-3 font-medium">Remaining</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($studentsNeedingPayment as $student)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $student->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->phone }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->email ?: '-' }}</td>
                            <td class="px-6 py-4 text-rose-600">{{ $student->getRemainingSessions() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">All students still have available sessions.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (auth()->user()->isTeacher())
        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Jadwal Mingguan Saya</h3>
                        <p class="text-sm text-slate-500">Ringkasan jadwal aktif Anda minggu ini.</p>
                    </div>
                    <a href="{{ route('my-schedule.index') }}" class="text-sm font-medium text-slate-700">Lihat semua</a>
                </div>

                <div class="mt-4 space-y-4">
                    @foreach ($mySchedule as $day)
                        @if ($day->items->isEmpty())
                            @continue
                        @endif

                        <div>
                            <h4 class="font-medium text-slate-900">{{ $day->label }}</h4>
                            <div class="mt-2 space-y-2">
                                @foreach ($day->items as $schedule)
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                                        <p class="font-medium text-slate-900">{{ $schedule->timeRangeLabel() }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $schedule->title ?: 'Tanpa info kelas khusus' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if ($mySchedule->every(fn ($day) => $day->items->isEmpty()))
                        <p class="text-sm text-slate-500">Belum ada jadwal aktif untuk Anda.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Ketersediaan Saya</h3>
                        <p class="text-sm text-slate-500">Blok waktu aktif yang bisa dipakai admin untuk menjadwalkan kelas.</p>
                    </div>
                    <a href="{{ route('my-availability.index') }}" class="text-sm font-medium text-slate-700">Kelola</a>
                </div>

                <div class="mt-4 space-y-4">
                    @foreach ($myAvailability as $day)
                        @if ($day->items->isEmpty())
                            @continue
                        @endif

                        <div>
                            <h4 class="font-medium text-slate-900">{{ $day->label }}</h4>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($day->items as $availability)
                                    <span class="rounded-full px-3 py-2 text-sm font-medium {{ $availability->status === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $availability->timeRangeLabel() }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if ($myAvailability->every(fn ($day) => $day->items->isEmpty()))
                        <p class="text-sm text-slate-500">Belum ada slot ketersediaan aktif.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
