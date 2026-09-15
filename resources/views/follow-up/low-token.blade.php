<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Token Menipis</h2>
            <p class="text-sm text-slate-500">Murid aktif dengan sisa token ≤ {{ \App\Models\Student::LOW_SESSION_THRESHOLD }}. Hari ini <span class="font-medium text-slate-700">{{ $todayLabel }}</span> — tombol WA aktif untuk murid yang <span class="font-medium text-slate-700">ada kelas hari ini</span>, jadi tinggal klik.</p>
        </div>
    </x-slot>

    <div class="rounded-3xl bg-white shadow-sm ring-1 ring-amber-100">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">⚠️ Perlu diingatkan</h3>
                <p class="text-sm text-slate-500">Klik tombol WA untuk mengingatkan dengan pesan otomatis.</p>
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
                                @if (! $waUrl)
                                    <span class="text-xs text-slate-400">No. HP kosong</span>
                                @elseif ($student->has_class_today)
                                    <div class="flex flex-col items-end gap-1">
                                        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                            Ingatkan via WA
                                        </a>
                                        <span class="text-xs font-medium text-emerald-600">Ada kelas hari ini</span>
                                    </div>
                                @elseif ($student->has_schedule)
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-400" title="Tombol aktif saat murid ada kelas">
                                            Ingatkan via WA
                                        </span>
                                        <span class="text-xs text-slate-400">Les: {{ $student->class_days_label }}</span>
                                    </div>
                                @else
                                    <div class="flex flex-col items-end gap-1">
                                        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                                            Ingatkan via WA
                                        </a>
                                        <span class="text-xs text-slate-400">Jadwal belum diatur</span>
                                    </div>
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
</x-app-layout>
