<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Ajukan Libur</h2>
                <p class="text-sm text-slate-500">Ajukan tanggal libur Anda. Admin akan meninjau & mencarikan pengganti.</p>
            </div>
            <a href="{{ route('my-leave.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Ajukan Libur</a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @forelse ($leaves as $leave)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-white p-5 shadow-sm">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-slate-900">{{ $leave->dayLabel() }}, {{ $leave->date->format('d M Y') }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($leave->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ $leave->statusLabel() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $leave->timeRangeLabel() }}@if ($leave->reason) · {{ $leave->reason }}@endif</p>
                </div>
                @if ($leave->isPending())
                    <form method="POST" action="{{ route('my-leave.destroy', $leave) }}" data-confirm="Batalkan pengajuan libur ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batalkan</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Belum ada pengajuan libur. Klik "Ajukan Libur" untuk mulai.
            </div>
        @endforelse
    </div>
</x-app-layout>
