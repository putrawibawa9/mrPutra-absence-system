<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Ketersediaan Guru</h2>
                <p class="text-sm text-slate-500">Kelola blok waktu available dan unavailable untuk setiap guru.</p>
            </div>
            <a href="{{ route('teacher-availabilities.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Tambah Ketersediaan</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($availabilities as $availability)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $availability->teacher->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $availability->dayLabel() }} | {{ $availability->timeRangeLabel() }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $availability->status === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $availability->statusLabel() }}
                    </span>
                </div>
                <div class="mt-4 space-y-1 text-sm text-slate-600">
                    <p>Status aktif: {{ $availability->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                    <p>Catatan: {{ $availability->notes ?: '-' }}</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('teacher-availabilities.edit', $availability) }}" class="text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('teacher-availabilities.destroy', $availability) }}" data-confirm="Hapus ketersediaan guru ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">Belum ada data ketersediaan guru.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Guru</th>
                    <th class="px-6 py-3 font-medium">Hari</th>
                    <th class="px-6 py-3 font-medium">Jam</th>
                    <th class="px-6 py-3 font-medium">Slot</th>
                    <th class="px-6 py-3 font-medium">Aktif</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($availabilities as $availability)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $availability->teacher->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $availability->dayLabel() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $availability->timeRangeLabel() }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $availability->status === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $availability->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $availability->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('teacher-availabilities.edit', $availability) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('teacher-availabilities.destroy', $availability) }}" data-confirm="Hapus ketersediaan guru ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada data ketersediaan guru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $availabilities->links() }}
    </div>
</x-app-layout>
