<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Pencocokan Jadwal</h2>
                <p class="text-sm text-slate-500">Cocokkan preferensi waktu murid (dari pendaftaran) dengan ketersediaan guru — otomatis, tanpa cek manual.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('schedule-match.index', ['scope' => 'pending']) }}" class="inline-flex justify-center rounded-xl px-4 py-2 text-sm font-medium {{ $scope === 'pending' ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700' }}">Menunggu</a>
                <a href="{{ route('schedule-match.index', ['scope' => 'all']) }}" class="inline-flex justify-center rounded-xl px-4 py-2 text-sm font-medium {{ $scope === 'all' ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700' }}">Semua</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        @unless ($hasAvailabilityData)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Belum ada data ketersediaan guru yang aktif. Minta guru mengisi jam kosongnya di
                <a href="{{ route('teacher-availabilities.index') }}" class="font-semibold underline">Ketersediaan Guru</a> supaya pencocokan bisa jalan.
            </div>
        @endunless

        <div class="rounded-2xl bg-white px-5 py-3 text-sm text-slate-600 shadow-sm">
            {{ $rows->count() }} pendaftaran ditampilkan · <span class="font-semibold text-emerald-600">{{ $matchedCount }}</span> sudah ada guru yang cocok.
        </div>

        @forelse ($rows as $row)
            @php($reg = $row->registration)
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $reg->student_name }}</h3>
                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $reg->programLabel() }}</span>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $reg->formatLabel() }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $reg->isPending() ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">{{ $reg->statusLabel() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $reg->phone ?: '-' }}</p>
                        <div class="mt-3 grid gap-1 text-sm">
                            <p class="text-slate-600"><span class="text-slate-400">Hari diinginkan:</span> {{ $reg->availableDayLabels() }}</p>
                            <p class="text-slate-600"><span class="text-slate-400">Waktu diinginkan:</span> {{ $reg->timePreferenceLabels() }}</p>
                        </div>
                        @if ($row->incomplete)
                            <p class="mt-2 text-xs text-amber-700">Preferensi murid belum lengkap — pencocokan memakai dimensi yang tersedia saja.</p>
                        @endif
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $row->match_count > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                            {{ $row->match_count }} guru cocok
                        </span>
                    </div>
                </div>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    @if ($row->match_count > 0)
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach ($row->matches as $match)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="font-medium text-slate-900">{{ $match->teacher?->name ?? 'Guru' }}</p>
                                        <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-500">{{ $match->slot_count }} slot</span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($match->slots as $slot)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                {{ $slot->day_label }} {{ $slot->time_label }}
                                                <span class="text-emerald-500">· {{ $slot->bucket_label }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-rose-600">Belum ada guru yang cocok dengan preferensi ini. Coba cek ketersediaan guru atau tawarkan jadwal alternatif.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Tidak ada pendaftaran {{ $scope === 'pending' ? 'yang menunggu' : '' }}.
            </div>
        @endforelse
    </div>
</x-app-layout>
