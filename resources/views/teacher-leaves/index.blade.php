<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Pengajuan Libur Guru</h2>
                <p class="text-sm text-slate-500">Setujui/tolak libur guru. Sistem otomatis merekomendasikan guru pengganti yang available.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
                    <a href="{{ route('teacher-leaves.index', ['scope' => $key]) }}" class="inline-flex justify-center rounded-xl px-3 py-2 text-sm font-medium {{ $scope === $key ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700' }}">
                        {{ $label }}@if ($key === 'pending' && $pendingCount > 0) ({{ $pendingCount }})@endif
                    </a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @forelse ($rows as $row)
            @php($leave = $row->leave)
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $leave->teacher?->name ?? 'Guru' }}</h3>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($leave->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ $leave->statusLabel() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-700">
                            <span class="font-medium">{{ $leave->dayLabel() }}, {{ $leave->date->format('d M Y') }}</span>
                            <span class="text-slate-400">·</span> {{ $leave->timeRangeLabel() }}
                        </p>
                        @if ($leave->reason)
                            <p class="mt-1 text-sm text-slate-500">Alasan: {{ $leave->reason }}</p>
                        @endif
                        @if ($leave->reviewer)
                            <p class="mt-1 text-xs text-slate-400">Diproses oleh {{ $leave->reviewer->name }} · {{ optional($leave->reviewed_at)->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                    @if ($leave->isPending())
                        <div class="flex shrink-0 gap-2">
                            <form method="POST" action="{{ route('teacher-leaves.approve', $leave) }}" data-confirm="Setujui libur guru ini?">
                                @csrf
                                <button type="submit" class="inline-flex justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('teacher-leaves.reject', $leave) }}" data-confirm="Tolak libur guru ini?">
                                @csrf
                                <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Tolak</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rekomendasi guru pengganti ({{ $row->substitute_count }})</p>
                    @if ($row->substitute_count > 0)
                        <div class="mt-2 space-y-2">
                            @foreach ($row->substitutes as $sub)
                                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-2.5">
                                    <span class="text-sm font-medium text-slate-900">{{ $sub->teacher?->name ?? 'Guru' }}</span>
                                    @foreach ($sub->slots as $slot)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $slot->day_label }} {{ $slot->time_label }}</span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm text-rose-600">Belum ada guru lain yang available di waktu itu. Perlu atur ulang jadwal secara manual.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Tidak ada pengajuan libur {{ $scope === 'pending' ? 'yang menunggu' : '' }}.
            </div>
        @endforelse
    </div>
</x-app-layout>
