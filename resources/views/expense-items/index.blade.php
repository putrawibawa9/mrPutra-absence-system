<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Item Expense</h2>
                <p class="text-sm text-slate-500">Master item mengunci kategori. Pilih item saat input expense, kategori mengikuti otomatis.</p>
            </div>
            <a href="{{ route('expense-items.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">+ Tambah Item</a>
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
                            <th class="px-6 py-3 font-medium">Item</th>
                            <th class="px-6 py-3 font-medium">Kategori</th>
                            <th class="px-6 py-3 font-medium">Keywords</th>
                            <th class="px-6 py-3 font-medium text-right">Dipakai</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($expenseItems as $item)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $item->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $item->keywords ?: '-' }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">{{ $item->expenses_count }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('expense-items.edit', $item) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                        <form method="POST" action="{{ route('expense-items.toggle-status', $item) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-slate-700">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        @if ($item->expenses_count === 0)
                                            <form method="POST" action="{{ route('expense-items.destroy', $item) }}" data-confirm="Hapus item ini?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-rose-600">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $expenseItems->links() }}</div>
    </div>
</x-app-layout>
