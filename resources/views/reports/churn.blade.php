<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Churn Murid</h2>
                <p class="text-sm text-slate-500">Laju murid berhenti tiap bulan & rata-ratanya. Churn = murid keluar ÷ murid aktif di awal bulan.</p>
            </div>
            <form method="GET" action="{{ route('reports.churn') }}">
                <select name="months" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm">
                    @foreach ([6, 12, 24] as $opt)
                        <option value="{{ $opt }}" @selected($months === $opt)>{{ $opt }} bulan terakhir</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Rata-rata Churn / Bulan</p>
                <p class="mt-2 text-3xl font-semibold {{ $avgChurn > 10 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($avgChurn, 1, ',', '.') }}%</p>
                <p class="mt-2 text-xs text-slate-500">Rata-rata {{ $months }} bulan terakhir.</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Murid Aktif Sekarang</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $activeNow }}</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Total Keluar ({{ $months }} bln)</p>
                <p class="mt-2 text-3xl font-semibold text-amber-600">{{ $totalChurned }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Churn per Bulan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Bulan</th>
                            <th class="px-6 py-3 font-medium text-right">Aktif Awal</th>
                            <th class="px-6 py-3 font-medium text-right">Masuk</th>
                            <th class="px-6 py-3 font-medium text-right">Keluar</th>
                            <th class="px-6 py-3 font-medium">Churn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $row->label }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">{{ $row->active_start }}</td>
                                <td class="px-6 py-4 text-right text-emerald-600">+{{ $row->joined }}</td>
                                <td class="px-6 py-4 text-right text-rose-600">-{{ $row->churned }}</td>
                                <td class="px-6 py-4">
                                    @if ($row->rate === null)
                                        <span class="text-xs text-slate-400">—</span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <div class="overflow-hidden rounded-full bg-slate-100" style="height:8px;width:96px;">
                                                <div class="rounded-full" style="height:100%;width:{{ min(100, $row->rate) }}%;background-color:{{ $row->rate > 10 ? '#e11d48' : '#10b981' }};"></div>
                                            </div>
                                            <span class="text-sm font-semibold {{ $row->rate > 10 ? 'text-rose-600' : 'text-slate-900' }}">{{ number_format($row->rate, 1, ',', '.') }}%</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-xs text-slate-400">Catatan: "Aktif Awal" = murid yang sudah terdaftar sebelum bulan itu & belum keluar. Bulan dengan 0 murid aktif awal ditandai "—" dan tidak masuk hitungan rata-rata.</p>
    </div>
</x-app-layout>
