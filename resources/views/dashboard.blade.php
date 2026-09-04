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

    <div class="mt-6 rounded-3xl bg-white shadow-sm ring-1 ring-amber-100">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">⚠️ Token Menipis — perlu diingatkan</h3>
                <p class="text-sm text-slate-500">Murid aktif dengan sisa token ≤ {{ \App\Models\Student::LOW_SESSION_THRESHOLD }}. Klik tombol WA untuk mengingatkan dengan pesan otomatis.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $lowTokenStudents->count() }} murid</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Murid</th>
                        <th class="px-6 py-3 font-medium">No. HP</th>
                        <th class="px-6 py-3 font-medium">Sisa Token</th>
                        <th class="px-6 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($lowTokenStudents as $student)
                        @php
                            $remaining = (int) ($student->payments_sum_remaining_sessions ?? 0);
                            $net = $remaining - (int) ($student->token_debt_count ?? 0);
                            $waUrl = $student->lowSessionReminderWhatsAppUrl($remaining);
                        @endphp
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <a href="{{ route('students.show', $student) }}" class="hover:underline">{{ $student->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->phone ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $net > 0 ? 'bg-amber-50 text-amber-700' : ($net < 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $net }} token
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if ($waUrl)
                                    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                        Ingatkan via WA
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">No. HP kosong</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">Semua murid masih punya cukup token. 🎉</td>
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
