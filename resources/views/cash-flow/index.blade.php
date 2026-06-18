<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Untung / Rugi per Periode</h2>
                <p class="text-sm text-slate-500">Pendapatan diakui per sesi belajar (akrual), sejajar dengan fee guru per pertemuan.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('expenses.create') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Add Expense</a>
                <a href="{{ route('expense-categories.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Expense Categories</a>
                <a href="{{ route('learning-modules.index') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Manage Modules</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('cash-flow.index') }}" class="grid gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="date_from" value="Date From" />
                    <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_from']" />
                </div>
                <div>
                    <x-input-label for="date_to" value="Date To" />
                    <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_to']" />
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white md:w-auto">Apply Filter</button>
                    <a href="{{ route('cash-flow.index') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 md:w-auto">Reset</a>
                </div>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Margin</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($margin, 1, ',', '.') }}%</p>
                <p class="mt-2 text-xs text-slate-500">Net profit dibanding revenue pada periode ini.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Rata-rata Pendapatan per Sesi</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($averageRevenuePerMeeting, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Pendapatan kelas dibagi {{ $tokenSessionCount }} sesi belajar pada periode ini.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Revenue</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">Rp {{ number_format($revenue, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Pendapatan kelas (per sesi belajar) + penjualan buku/modul pada periode ini.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Net Profit</p>
                <p class="mt-3 text-3xl font-semibold {{ $netProfit >= 0 ? 'text-slate-900' : 'text-rose-700' }}">Rp {{ number_format($netProfit, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Revenue dikurangi seluruh expense pada periode ini.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Pendapatan Kelas</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($incomeBySource['student_payments'], 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Diakui per sesi belajar yang terjadi.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Pendapatan Buku/Modul</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($incomeBySource['module_payments'], 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Diakui saat penjualan buku atau modul.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Fee Guru</p>
                <p class="mt-3 text-2xl font-semibold text-rose-700">Rp {{ number_format($teacherFeeTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Otomatis dari pertemuan pada periode ini.</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Expense</p>
                <p class="mt-3 text-2xl font-semibold text-rose-700">Rp {{ number_format($expenseTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">Fee guru + seluruh expense operasional.</p>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-1">
                <h3 class="text-lg font-semibold text-slate-900">Margin per Tipe Sesi</h3>
                <p class="text-sm text-slate-500">Margin kotor = pendapatan − fee guru per sesi (sebelum dikurangi expense operasional bersama).</p>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @php($private = $sessionTypeBreakdown['private'])
                @php($group = $sessionTypeBreakdown['group'])

                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-semibold text-slate-900">Sesi Private (1-on-1)</p>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $private['sessions'] }} sesi</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Margin kotor</p>
                    <p class="text-2xl font-semibold {{ $private['margin'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rp {{ number_format($private['margin'], 0, ',', '.') }}</p>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Pendapatan</dt><dd class="font-medium text-slate-900">Rp {{ number_format($private['revenue'], 0, ',', '.') }}</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Fee guru</dt><dd class="font-medium text-rose-700">− Rp {{ number_format($private['fee'], 0, ',', '.') }}</dd></div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2"><dt class="text-slate-500">Rata-rata margin / sesi</dt><dd class="font-semibold text-slate-900">Rp {{ number_format($private['avg_margin'], 0, ',', '.') }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-semibold text-slate-900">Sesi Grup (&gt;1 peserta)</p>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $group['sessions'] }} sesi · {{ $group['participants'] }} peserta</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Margin kotor</p>
                    <p class="text-2xl font-semibold {{ $group['margin'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rp {{ number_format($group['margin'], 0, ',', '.') }}</p>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Pendapatan</dt><dd class="font-medium text-slate-900">Rp {{ number_format($group['revenue'], 0, ',', '.') }}</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Fee guru</dt><dd class="font-medium text-rose-700">− Rp {{ number_format($group['fee'], 0, ',', '.') }}</dd></div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2"><dt class="text-slate-500">Rata-rata margin / sesi</dt><dd class="font-semibold text-slate-900">Rp {{ number_format($group['avg_margin'], 0, ',', '.') }}</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Rata-rata peserta / sesi</dt><dd class="font-medium text-slate-900">{{ number_format($group['avg_participants'], 1, ',', '.') }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Net per Sesi</h3>
                        <p class="text-sm text-slate-500">Net income tiap pertemuan = pendapatan kelas − fee guru (grup digabung per kelas).</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $sessionNetEntries->count() }} sesi</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($sessionNetEntries as $netEntry)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $netEntry->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $netEntry->label }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $netEntry->date->format('d M Y') }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="font-semibold {{ $netEntry->net >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rp {{ number_format($netEntry->net, 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Rp {{ number_format($netEntry->gross, 0, ',', '.') }} − Rp {{ number_format($netEntry->fee, 0, ',', '.') }} fee</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">Belum ada sesi pada periode ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Expense Entries</h3>
                        <p class="text-sm text-slate-500">Pengeluaran operasional pada periode ini. Fee guru tidak ditampilkan di sini (lihat list Net per Sesi).</p>
                    </div>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $expenses->count() }} entry</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($expenses as $expense)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $expense->title }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $expense->category->name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $expense->expense_date->format('d M Y') }} · {{ $expense->creator?->name ?? '-' }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="font-semibold text-rose-700">Rp {{ number_format($expense->amount, 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $expense->notes ?: 'Expense record' }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No expense entry in this period.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
