<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Games</h2>
                <p class="text-sm text-slate-500">Kumpulan game (tema + link) siap pakai, biar guru tinggal sekali klik saat absen.</p>
            </div>
            <a href="{{ route('games.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Tambah Game</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($games as $game)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">{{ $game->label() }}</h3>
                        <a href="{{ $game->link }}" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-sm text-emerald-700 hover:underline">{{ $game->link }}</a>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $game->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $game->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ $game->link }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center rounded-xl border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700">Buka</a>
                    <a href="{{ route('games.edit', $game) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('games.destroy', $game) }}" data-confirm="Hapus game ini? Jika pernah dipakai di sesi, game hanya akan dinonaktifkan.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">Belum ada game.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Tema</th>
                    <th class="px-6 py-3 font-medium">Link</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($games as $game)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $game->label() }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ $game->link }}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 hover:underline">{{ \Illuminate\Support\Str::limit($game->link, 50) }}</a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $game->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $game->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ $game->link }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-emerald-700">Buka</a>
                                <a href="{{ route('games.edit', $game) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('games.destroy', $game) }}" data-confirm="Hapus game ini? Jika pernah dipakai di sesi, game hanya akan dinonaktifkan.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">Belum ada game.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $games->links() }}
    </div>
</x-app-layout>
