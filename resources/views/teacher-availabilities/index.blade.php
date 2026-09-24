<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Ketersediaan Guru</h2>
                <p class="text-sm text-slate-500">Blok waktu tiap guru — dikelompokkan per guru agar mudah dibaca. {{ $teacherCards->count() }} guru · {{ $totalSlots }} slot.</p>
            </div>
            <a href="{{ route('teacher-availabilities.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Tambah Ketersediaan</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="space-y-4">
        @forelse ($teacherCards as $card)
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white">
                        {{ strtoupper(mb_substr($card->teacher?->name ?? '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $card->teacher?->name ?? 'Guru' }}</h3>
                        <p class="text-xs text-slate-500">
                            {{ $card->slots->count() }} slot
                            <span class="text-emerald-600">· {{ $card->available_count }} available</span>
                            @if ($card->unavailable_count > 0)
                                <span class="text-amber-600">· {{ $card->unavailable_count }} unavailable</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    @foreach ($card->slots as $slot)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-2.5 {{ $slot->is_active ? '' : 'opacity-60' }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex justify-center rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $slot->dayLabel() }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $slot->timeRangeLabel() }}</span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $slot->status === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $slot->statusLabel() }}</span>
                                @unless ($slot->is_active)
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                @endunless
                            </div>
                            <div class="flex items-center gap-3 text-sm font-medium">
                                <a href="{{ route('teacher-availabilities.edit', $slot) }}" class="text-slate-700 hover:text-slate-900">Edit</a>
                                <form method="POST" action="{{ route('teacher-availabilities.destroy', $slot) }}" data-confirm="Hapus ketersediaan guru ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-700">Hapus</button>
                                </form>
                            </div>
                        </div>
                        @if ($slot->notes)
                            <p class="px-4 text-xs text-slate-400">Catatan: {{ $slot->notes }}</p>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Belum ada data ketersediaan guru. Klik "Tambah Ketersediaan" untuk mulai.
            </div>
        @endforelse
    </div>
</x-app-layout>
