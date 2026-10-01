<x-guest-layout>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>

    <div class="text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Slip Gaji</p>
        <h2 class="mt-1 text-lg font-semibold text-slate-900">Mr. Putra Speak</h2>
        <p class="mt-1 text-xs text-slate-500">English & Coding Class</p>
    </div>

    <div class="mt-5 space-y-1 border-t border-b border-slate-100 py-4 text-sm">
        <div class="flex justify-between gap-3">
            <span class="text-slate-500">Guru</span>
            <span class="font-medium text-slate-900">{{ $teacher->name }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span class="text-slate-500">Periode</span>
            <span class="font-medium text-slate-900">{{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($dateTo)->format('d M Y') }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span class="text-slate-500">Jumlah Sesi</span>
            <span class="font-medium text-slate-900">{{ $sessionCount }} sesi</span>
        </div>
    </div>

    <div class="mt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Rincian Sesi</p>
        <div class="space-y-1">
            @forelse ($items as $item)
                <div class="flex items-start justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">{{ $item->date->format('d M Y') }}</p>
                        <p class="truncate text-slate-800">
                            {{ $item->desc }}
                            @if ($item->is_co)
                                <span class="ml-1 rounded-full bg-sky-50 px-2 py-1 text-xs text-sky-700">co-teacher</span>
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 font-medium text-slate-900">Rp {{ number_format($item->amount, 0, ',', '.') }}</span>
                </div>
            @empty
                <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">Tidak ada sesi pada periode ini.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between rounded-2xl bg-slate-900 px-4 py-3 text-white">
        <span class="text-sm font-medium">Total Gaji</span>
        <span class="text-lg font-semibold">Rp {{ number_format($total, 0, ',', '.') }}</span>
    </div>

    <p class="mt-3 text-center text-xs text-slate-400">Dibuat {{ $generatedAt->locale('id')->translatedFormat('d F Y, H:i') }} WITA</p>

    <div class="no-print mt-5 flex gap-2">
        <button type="button" onclick="window.print()" class="inline-flex flex-1 justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">Cetak / Simpan PDF</button>
    </div>
</x-guest-layout>
