<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Review Bulanan Expense</h2>
            <p class="text-sm text-slate-500">Deteksi potensi salah kategori: expense tanpa item, item lintas kategori, dan lonjakan biaya tetap.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('reports.review') }}" class="grid gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="date_from" value="Dari Tanggal" />
                    <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_from']" />
                </div>
                <div>
                    <x-input-label for="date_to" value="Sampai Tanggal" />
                    <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_to']" />
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white md:w-auto">Terapkan</button>
                    <a href="{{ route('reports.review') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 md:w-auto">Reset</a>
                </div>
            </form>
        </div>

        {{-- (a) Expense tanpa item --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Expense tanpa item ({{ $withoutItem->count() }})</h3>
                <p class="text-sm text-slate-500">Belum terkategorisasi lewat item — sebaiknya diperbaiki.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Tanggal</th>
                            <th class="px-6 py-3 font-medium">Judul</th>
                            <th class="px-6 py-3 font-medium">Kategori</th>
                            <th class="px-6 py-3 font-medium text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($withoutItem as $e)
                            <tr>
                                <td class="px-6 py-4 text-slate-600">{{ $e->expense_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $e->title }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $e->category?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-right text-rose-700">Rp {{ number_format($e->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-emerald-600">Semua expense pada periode ini sudah punya item. 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- (b) Item lintas kategori --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Item tercatat di lebih dari satu kategori ({{ $multiCategory->count() }})</h3>
                <p class="text-sm text-slate-500">Idealnya satu item selalu satu kategori. (Seluruh riwayat.)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Item</th>
                            <th class="px-6 py-3 font-medium">Kategori</th>
                            <th class="px-6 py-3 font-medium text-right">Jumlah kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($multiCategory as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $row->item }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $row->categories }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-amber-600">{{ $row->category_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-emerald-600">Tidak ada item lintas kategori. 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- (c) Lonjakan biaya tetap --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Biaya tetap naik &gt;30% ({{ $fixedSpikes->count() }})</h3>
                <p class="text-sm text-slate-500">Dibanding rata-rata 3 bulan sebelum periode.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Kategori</th>
                            <th class="px-6 py-3 font-medium text-right">Periode ini</th>
                            <th class="px-6 py-3 font-medium text-right">Rata 3 bln</th>
                            <th class="px-6 py-3 font-medium text-right">Naik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($fixedSpikes as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $row->name }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">Rp {{ number_format($row->current, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-500">Rp {{ number_format($row->prev_avg, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-rose-600">{{ $row->pct !== null ? '+'.number_format($row->pct, 1, ',', '.').'%' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-emerald-600">Tidak ada lonjakan biaya tetap. 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
