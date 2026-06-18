<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Link Materi</h2>
                <p class="text-sm text-slate-500">Kelola link materi yang nanti bisa di-assign ke setiap kelas secara opsional.</p>
            </div>
            <a href="{{ route('material-links.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Tambah Link Materi</a>
        </div>
    </x-slot>

    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('material-links.index') }}" class="grid gap-4 md:grid-cols-[1fr_auto_auto]">
            <div>
                <x-input-label for="search" value="Search Link Materi" />
                <x-text-input
                    id="search"
                    name="search"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-slate-300"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari judul, link, atau deskripsi"
                />
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white md:w-auto">
                    Search
                </button>
                <a href="{{ route('material-links.index') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 md:w-auto">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($materialLinks as $materialLink)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $materialLink->title }}</h3>
                        <a href="{{ $materialLink->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex break-all text-sm text-sky-700 hover:text-sky-900">
                            {{ $materialLink->url }}
                        </a>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $materialLink->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $materialLink->statusLabel() }}
                    </span>
                </div>
                <div class="mt-4 space-y-1 text-sm text-slate-600">
                    <p>Dipakai di jadwal: {{ $materialLink->teacher_schedules_count }}</p>
                    <p>Deskripsi: {{ $materialLink->description ?: '-' }}</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('material-links.edit', $materialLink) }}" class="text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('material-links.destroy', $materialLink) }}" data-confirm="Hapus link materi ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">Belum ada link materi.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Judul</th>
                    <th class="px-6 py-3 font-medium">Link</th>
                    <th class="px-6 py-3 font-medium">Dipakai di Jadwal</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($materialLinks as $materialLink)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900">{{ $materialLink->title }}</p>
                            @if ($materialLink->description)
                                <p class="mt-1 text-xs text-slate-500">{{ $materialLink->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <a href="{{ $materialLink->url }}" target="_blank" rel="noopener noreferrer" class="break-all font-medium text-sky-700 hover:text-sky-900">
                                {{ $materialLink->url }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $materialLink->teacher_schedules_count }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $materialLink->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $materialLink->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('material-links.edit', $materialLink) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('material-links.destroy', $materialLink) }}" data-confirm="Hapus link materi ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada link materi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $materialLinks->links() }}
    </div>
</x-app-layout>
