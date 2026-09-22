<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Komitmen / Cicilan</h2>
                <p class="text-sm text-slate-500">Biaya yang dipecah jadi cicilan bulanan. Menghapus komitmen menghapus seluruh barisnya.</p>
            </div>
            <a href="{{ route('expense-commitments.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">+ Buat Komitmen</a>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Komitmen</th>
                            <th class="px-6 py-3 font-medium">Item / Kategori</th>
                            <th class="px-6 py-3 font-medium text-right">Total</th>
                            <th class="px-6 py-3 font-medium text-right">Bulan</th>
                            <th class="px-6 py-3 font-medium">Mulai</th>
                            <th class="px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($commitments as $commitment)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $commitment->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $commitment->item?->name }} <span class="text-slate-400">·</span> {{ $commitment->item?->category?->name }}</td>
                                <td class="px-6 py-4 text-right font-medium text-rose-700">Rp {{ number_format($commitment->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">{{ $commitment->months }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $commitment->start_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('expense-commitments.destroy', $commitment) }}" data-confirm="Hapus komitmen ini beserta SEMUA baris cicilannya?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-600">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada komitmen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $commitments->links() }}</div>
    </div>
</x-app-layout>
