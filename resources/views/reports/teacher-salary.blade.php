<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Gaji Guru</h2>
            <p class="text-sm text-slate-500">Total fee mengajar per guru pada periode (termasuk fee co-teacher).</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('reports.teacher-salary') }}" class="grid gap-4 md:grid-cols-3">
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
                    <a href="{{ route('reports.teacher-salary') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 md:w-auto">Reset</a>
                </div>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Total Gaji Dibayar</p>
                <p class="mt-2 text-3xl font-semibold text-rose-700">Rp {{ number_format($totalPayout, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Jumlah Guru</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $teacherCount }}</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Total Sesi</p>
                <p class="mt-2 text-3xl font-semibold text-sky-600">{{ $sessionCount }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Rincian per Guru</h3>
                <p class="text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Guru</th>
                            <th class="px-6 py-3 font-medium text-right">Jumlah Sesi</th>
                            <th class="px-6 py-3 font-medium text-right">Total Gaji</th>
                            <th class="px-6 py-3 font-medium text-right">Slip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $row->teacher?->name ?? 'Guru' }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">
                                    {{ $row->session_count }}
                                    @if ($row->co_session_count > 0)
                                        <span class="ml-1 rounded-full bg-sky-50 px-2 py-1 text-xs text-sky-700">{{ $row->co_session_count }} co-teacher</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-900">Rp {{ number_format($row->total, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($row->slip_url)
                                        <a href="{{ $row->slip_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Kirim Slip Gaji</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">Belum ada fee guru pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($rows->isNotEmpty())
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td class="px-6 py-3 font-semibold text-slate-900">Total</td>
                                <td class="px-6 py-3 text-right font-semibold text-slate-900">{{ $sessionCount }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-rose-700">Rp {{ number_format($totalPayout, 0, ',', '.') }}</td>
                                <td class="px-6 py-3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
