<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500">Quick overview of new registrations, students who left this month, and active students.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (auth()->user()->isAdmin() || auth()->user()->isTeacher())
                    <a href="{{ route('attendances.create') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                        Catat Absensi
                    </a>
                @endif
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('payments.create') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                        + Pembayaran
                    </a>
                    <a href="{{ route('expenses.create') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        + Expense
                    </a>
                @endif
            </div>
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

    @if (! empty($pendingRegistrations) && $pendingRegistrations > 0)
        <a href="{{ route('registrations.index') }}" class="mt-6 flex items-center justify-between gap-4 rounded-3xl bg-slate-900 px-6 py-4 text-white shadow-sm transition hover:bg-slate-800">
            <div>
                <p class="text-sm font-semibold">{{ $pendingRegistrations }} pendaftaran murid baru menunggu</p>
                <p class="mt-1 text-sm text-slate-300">Klik untuk meninjau &amp; menerima calon murid.</p>
            </div>
            <span class="text-sm font-medium">Lihat &rarr;</span>
        </a>
    @endif

    @if (auth()->user()->isAdmin())
        @php($isToday = $selectedDate->isToday())
        <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('dashboard', ['date' => $selectedDate->copy()->subDay()->toDateString()]) }}"
                           class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50" aria-label="Hari sebelumnya">&larr;</a>
                        <a href="{{ route('dashboard', ['date' => $selectedDate->copy()->addDay()->toDateString()]) }}"
                           class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50" aria-label="Hari berikutnya">&rarr;</a>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $isToday ? 'Kelas Hari Ini' : 'Jadwal Kelas' }}</h3>
                        <p class="text-sm text-slate-500">{{ $todayLabel }}, {{ $selectedDate->locale('id')->translatedFormat('d F Y') }} — {{ $todaysClasses->count() }} kelas terjadwal.</p>
                    </div>
                </div>
                <a href="{{ route('classes.today') }}" class="text-sm font-medium text-slate-700">Lihat detail</a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($todaysClasses as $o)
                    <div class="flex flex-col gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between {{ $o->status === 'cancelled' ? 'opacity-60' : '' }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-sm font-bold tracking-wide text-white">{{ $o->start }}–{{ $o->end }}</span>
                                @if ($o->classroom?->code)
                                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold tracking-wide text-slate-700">{{ $o->classroom->code }}</span>
                                @endif
                                <span class="font-semibold text-slate-900">{{ $o->classroom?->name ?? ($o->schedule->title ?: 'Kelas') }}</span>
                                @if ($o->classroom)
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $o->classroom->isPrivate() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ $o->classroom->isPrivate() ? 'Private' : 'Grup' }}</span>
                                @endif
                                @if ($o->status === 'moved_in')
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Pindahan</span>
                                @elseif ($o->status === 'retimed')
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">Jam diubah</span>
                                @elseif ($o->status === 'cancelled')
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Dibatalkan</span>
                                @endif
                            </div>
                            @if ($o->students)
                                <p class="mt-1 truncate text-sm text-slate-600">Murid: {{ $o->students }}</p>
                            @endif
                        </div>
                        <div class="text-sm sm:text-right">
                            @if ($o->status === 'substitute')
                                <p class="text-amber-700">Guru: <span class="font-medium">{{ $o->effective_teacher?->name ?? '-' }}</span> <span class="text-xs text-slate-500">(gantikan {{ $o->original_teacher?->name ?? '-' }})</span></p>
                            @elseif ($o->status === 'leave_unassigned')
                                <p class="text-rose-700">{{ $o->effective_teacher?->name ?? '-' }} — guru libur, belum ada pengganti</p>
                            @else
                                <p class="text-slate-500">Guru: <span class="font-medium text-slate-700">{{ $o->effective_teacher?->name ?? 'Belum ada guru' }}</span></p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Tidak ada kelas terjadwal {{ $isToday ? 'hari ini' : 'pada tanggal ini' }}.</p>
                @endforelse
            </div>
        </div>

        {{-- Kelas yang sudah diabsen pada tanggal terpilih (bandingkan dgn jadwal di kiri) --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Sudah Diabsen {{ $isToday ? 'Hari Ini' : 'Tanggal Ini' }}</h3>
                    <p class="text-sm text-slate-500">Sesi yang sudah dicatat absennya — bandingkan dengan jadwal di kiri.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $attendedToday->count() }} sesi</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($attendedToday as $item)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ $item->label }}</span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->is_group ? 'bg-amber-50 text-amber-700' : 'bg-indigo-50 text-indigo-700' }}">{{ $item->is_group ? 'Grup' : 'Private' }}</span>
                            </div>
                            <p class="text-sm text-slate-500">Guru: <span class="font-medium text-slate-700">{{ $item->teacher ?? '-' }}</span></p>
                        </div>
                        @if ($item->students)
                            <p class="mt-1 truncate text-sm text-slate-600">Murid: {{ $item->students }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->game ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">&#127918; {{ $item->game ?? 'Tanpa game' }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->materi ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">Materi {{ $item->materi ? '✅' : '—' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Belum ada kelas yang diabsen {{ $isToday ? 'hari ini' : 'pada tanggal ini' }}.</p>
                @endforelse
            </div>
        </div>
        </div>

        <div class="mt-6 rounded-3xl bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Pertemuan Habis {{ $isToday ? 'Hari Ini' : 'Tanggal Ini' }}</h3>
                    <p class="text-sm text-slate-500">Murid yang ada kelas {{ $isToday ? 'hari ini' : 'pada tanggal ini' }} tapi sisa pertemuannya sudah 0 — perlu ditagih / perpanjang.</p>
                </div>
                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $habisToday->count() }} murid</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($habisToday as $item)
                    <div class="flex flex-col gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-sm font-bold tracking-wide text-white">{{ $item->schedule->timeRangeLabel() }}</span>
                            @if ($item->schedule->classroom?->code)
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold tracking-wide text-slate-700">{{ $item->schedule->classroom->code }}</span>
                            @endif
                            <span class="text-sm font-medium text-slate-600">{{ $item->schedule->classroom?->name ?? ($item->schedule->title ?: 'Kelas') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-rose-700">{{ $item->student->name }}</span>
                            @php($waUrl = $item->student->lowSessionReminderWhatsAppUrl())
                            @if ($waUrl)
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700">Ingatkan WA</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Tidak ada murid dengan pertemuan habis {{ $isToday ? 'hari ini' : 'pada tanggal ini' }}. 🎉</p>
                @endforelse
            </div>
        </div>
    @endif

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
