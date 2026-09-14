<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Monitoring OpEx</h2>
                <p class="text-sm text-slate-500">Biaya tetap vs variabel, dan biaya operasional sesungguhnya per kelas (overhead tetap dibagi rata ke tiap pertemuan).</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('expense-categories.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Kategori Expense</a>
                <a href="{{ route('expenses.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Expense</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('reports.opex') }}" class="grid gap-4 md:grid-cols-3">
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
                    <a href="{{ route('reports.opex') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 md:w-auto">Reset</a>
                </div>
            </form>
        </div>

        @if ($unclassifiedTotal > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Ada <strong>Rp {{ number_format($unclassifiedTotal, 0, ',', '.') }}</strong> biaya di kategori yang belum ditandai Tetap/Variabel. Biaya ini <strong>belum</strong> ikut dibebankan sebagai overhead per kelas — tandai kategorinya di
                <a href="{{ route('expense-categories.index') }}" class="font-semibold underline">Kategori Expense</a> supaya perhitungan per kelas akurat.
            </div>
        @endif

        {{-- Komposisi biaya periode --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total OpEx</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($opexTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Seluruh pengeluaran pada periode ini.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Biaya Tetap</p>
                <p class="mt-3 text-3xl font-semibold text-indigo-600">Rp {{ number_format($fixedTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Sewa, tools, partner — tetap walau kelas sedikit.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Biaya Variabel</p>
                <p class="mt-3 text-3xl font-semibold text-sky-600">Rp {{ number_format($variableTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Termasuk fee guru Rp {{ number_format($teacherFeeTotal, 0, ',', '.') }} (langsung ke kelas).</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Belum Ditandai</p>
                <p class="mt-3 text-3xl font-semibold {{ $unclassifiedTotal > 0 ? 'text-amber-600' : 'text-slate-400' }}">Rp {{ number_format($unclassifiedTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Perlu ditandai Tetap/Variabel.</p>
            </div>
        </div>

        {{-- Biaya per kelas (loaded) --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Biaya Operasional per Kelas</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $meetingCount }} pertemuan pada periode ini. Overhead tetap dibagi rata ke tiap pertemuan.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Overhead tetap / pertemuan</p>
                    <p class="mt-2 text-2xl font-semibold text-indigo-600">Rp {{ number_format($overheadPerMeeting, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Fee guru / pertemuan (rata)</p>
                    <p class="mt-2 text-2xl font-semibold text-sky-600">Rp {{ number_format($feePerMeeting, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Biaya penuh / pertemuan</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($loadedCostPerMeeting, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Rata pendapatan / pertemuan</p>
                    <p class="mt-2 text-2xl font-semibold {{ $avgRevenuePerMeeting >= $loadedCostPerMeeting ? 'text-emerald-600' : 'text-rose-600' }}">Rp {{ number_format($avgRevenuePerMeeting, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Margin rata per pertemuan (setelah dibebani overhead)</p>
                    @php($avgLoadedMargin = $avgRevenuePerMeeting - $loadedCostPerMeeting)
                    <p class="mt-2 text-2xl font-semibold {{ $avgLoadedMargin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Rp {{ number_format($avgLoadedMargin, 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-slate-500">Kalau minus, rata-rata kelas belum menutup biaya penuhnya.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Break-even (nutup biaya tetap)</p>
                    @if (! is_null($breakEvenMeetings))
                        <p class="mt-2 text-2xl font-semibold {{ $meetingCount >= $breakEvenMeetings ? 'text-emerald-600' : 'text-slate-900' }}">{{ $breakEvenMeetings }} pertemuan / periode</p>
                        <p class="mt-2 text-xs text-slate-500">Dengan kontribusi Rp {{ number_format($contributionPerMeeting, 0, ',', '.') }}/pertemuan (pendapatan − fee guru). Aktual periode ini: {{ $meetingCount }} pertemuan{{ $meetingCount >= $breakEvenMeetings ? ' — sudah menutup biaya tetap.' : '.' }}</p>
                    @else
                        <p class="mt-2 text-2xl font-semibold text-rose-600">Belum tercapai</p>
                        <p class="mt-2 text-xs text-slate-500">Kontribusi per pertemuan (pendapatan − fee guru) belum positif, jadi biaya tetap tak akan tertutup dari volume saja.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Kelas yang "bocor" --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Net Sesungguhnya per Kelas</h3>
                <p class="text-sm text-slate-500">
                    Pendapatan − fee guru − jatah overhead. Diurutkan dari yang paling rugi.
                    @if ($leakCount > 0)
                        <span class="font-semibold text-rose-600">{{ $leakCount }} kelas rugi setelah dibebani overhead (bocor halus).</span>
                    @else
                        <span class="font-semibold text-emerald-600">Tidak ada kelas yang rugi setelah dibebani overhead.</span>
                    @endif
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Tanggal</th>
                            <th class="px-6 py-3 font-medium">Kelas</th>
                            <th class="px-6 py-3 font-medium text-right">Pendapatan</th>
                            <th class="px-6 py-3 font-medium text-right">Fee Guru</th>
                            <th class="px-6 py-3 font-medium text-right">Overhead</th>
                            <th class="px-6 py-3 font-medium text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($classEntries as $entry)
                            <tr class="{{ $entry->net < 0 ? 'bg-rose-50/60' : '' }}">
                                <td class="px-6 py-4 text-slate-600">{{ optional($entry->date)->format('d M Y') ?? '-' }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $entry->name }}
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $entry->label }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($entry->gross, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($entry->fee, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($entry->overhead, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-semibold {{ $entry->net < 0 ? 'text-rose-600' : 'text-emerald-600' }}">Rp {{ number_format($entry->net, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada kelas berbayar pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Rincian per kategori --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Rincian OpEx per Kategori</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Kategori</th>
                            <th class="px-6 py-3 font-medium">Perilaku</th>
                            <th class="px-6 py-3 font-medium text-right">Jml Transaksi</th>
                            <th class="px-6 py-3 font-medium text-right">Total</th>
                            <th class="px-6 py-3 font-medium text-right">% OpEx</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($byCategory as $cat)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $cat->name }}</td>
                                <td class="px-6 py-4">
                                    @if ($cat->behavior === \App\Models\ExpenseCategory::COST_FIXED)
                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Tetap</span>
                                    @elseif ($cat->behavior === \App\Models\ExpenseCategory::COST_VARIABLE)
                                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">Variabel</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Belum ditandai</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">{{ $cat->count }}</td>
                                <td class="px-6 py-4 text-right font-medium text-slate-900">Rp {{ number_format($cat->amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-500">{{ $opexTotal > 0 ? number_format($cat->amount / $opexTotal * 100, 1, ',', '.') : '0' }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada expense pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
