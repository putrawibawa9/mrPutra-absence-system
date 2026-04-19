<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Ketersediaan Mengajar Saya</h2>
                <p class="text-sm text-slate-500">Kelola blok waktu Anda agar admin mudah menyusun jadwal.</p>
            </div>
            <a href="{{ route('my-availability.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Tambah Slot</a>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($groupedAvailabilities as $day)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ $day->label }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($day->items as $availability)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $availability->timeRangeLabel() }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $availability->statusLabel() }} | {{ $availability->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                                </div>
                                <div class="flex items-center gap-3 text-sm font-medium">
                                    <a href="{{ route('my-availability.edit', $availability) }}" class="text-slate-700">Edit</a>
                                    <form method="POST" action="{{ route('my-availability.destroy', $availability) }}" data-confirm="Hapus slot ketersediaan ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600">Delete</button>
                                    </form>
                                </div>
                            </div>
                            @if ($availability->notes)
                                <p class="mt-2 text-sm text-slate-500">{{ $availability->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada slot ketersediaan.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
