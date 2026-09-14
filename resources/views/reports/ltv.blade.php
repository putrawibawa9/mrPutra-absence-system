<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">LTV Murid</h2>
            <p class="text-sm text-slate-500">Berapa kali tiap murid membayar (siklus token), total token dibeli, dan total nilai (lifetime).</p>
        </div>
    </x-slot>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Murid pernah bayar</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $payersCount }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Bayar ≥ 2 kali (re-purchase)</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-600">{{ $repeatCount }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Rata-rata kali bayar</p>
            <p class="mt-2 text-3xl font-semibold text-sky-600">{{ $avgCycles }}x</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total nilai (semua murid)</p>
            <p class="mt-2 text-3xl font-semibold text-indigo-600">Rp {{ number_format($totalLtv, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Murid</th>
                        <th class="px-6 py-3 font-medium text-right">Kali Bayar</th>
                        <th class="px-6 py-3 font-medium text-right">Token Dibeli</th>
                        <th class="px-6 py-3 font-medium text-right">Total Dibayar (LTV)</th>
                        <th class="px-6 py-3 font-medium">Bayar Pertama</th>
                        <th class="px-6 py-3 font-medium">Bayar Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <a href="{{ route('students.show', $student) }}" class="hover:underline">{{ $student->name }}</a>
                                @unless ($student->is_active)
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">non-aktif</span>
                                @endunless
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ (int) $student->token_payment_count }}x</td>
                            <td class="px-6 py-4 text-right text-slate-600">{{ (int) $student->tokens_purchased }}</td>
                            <td class="px-6 py-4 text-right font-medium text-slate-900">Rp {{ number_format((int) $student->ltv_amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->first_payment_date ? \Illuminate\Support\Carbon::parse($student->first_payment_date)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->last_payment_date ? \Illuminate\Support\Carbon::parse($student->last_payment_date)->format('d M Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada murid yang membayar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
