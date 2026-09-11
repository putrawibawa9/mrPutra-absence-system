<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-semibold text-slate-900">Pendaftaran Murid</h2>
            <p class="text-sm text-slate-500">Pendaftaran masuk dari form publik. Terima untuk membuat murid baru.</p>
        </div>
    </x-slot>

    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-slate-900">Link form pendaftaran (bagikan ke calon murid)</p>
                <a href="{{ route('registrations.create') }}" target="_blank" rel="noopener noreferrer" class="text-sm text-sky-700 hover:underline">{{ route('registrations.create') }}</a>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $pendingCount }} menunggu</span>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($registrations as $reg)
            <div class="rounded-3xl bg-white p-5 shadow-sm {{ $reg->isPending() ? '' : 'opacity-70' }}">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-lg font-semibold text-slate-900">{{ $reg->student_name }}</span>
                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $reg->programLabel() }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $reg->status === \App\Models\Registration::STATUS_PENDING ? 'bg-amber-50 text-amber-700' : ($reg->status === \App\Models\Registration::STATUS_ACCEPTED ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">{{ $reg->statusLabel() }}</span>
                    </div>
                    <span class="text-xs text-slate-400">{{ $reg->created_at?->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    <p><span class="text-slate-500">WhatsApp:</span> <span class="font-medium text-slate-800">{{ $reg->phone }}</span> &middot; <span class="text-slate-500">Umur:</span> {{ $reg->age ? $reg->age.' tahun' : '-' }}</p>
                    <p><span class="text-slate-500">Wali:</span> {{ $reg->guardian_name ?: '-' }} &middot; <span class="text-slate-500">Email:</span> {{ $reg->email ?: '-' }}</p>
                    <p><span class="text-slate-500">Format:</span> {{ $reg->formatLabel() }} &middot; <span class="text-slate-500">Level:</span> {{ $reg->level_note ?: '-' }}</p>
                    <p><span class="text-slate-500">Hari bisa:</span> {{ $reg->availableDayLabels() }}</p>
                    <p><span class="text-slate-500">Waktu:</span> {{ $reg->timePreferenceLabels() }}</p>
                    @if ($reg->goal)
                        <p><span class="text-slate-500">Tujuan:</span> {{ $reg->goal }}</p>
                    @endif
                    @if ($reg->referral_source)
                        <p><span class="text-slate-500">Sumber:</span> {{ $reg->referral_source }}</p>
                    @endif
                    @if ($reg->notes)
                        <p><span class="text-slate-500">Catatan:</span> {{ $reg->notes }}</p>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if ($reg->isPending())
                        @php $waUrl = ($n = preg_replace('/\D+/', '', $reg->phone)) ? 'https://wa.me/'.(\Illuminate\Support\Str::startsWith($n,'0') ? '62'.substr($n,1) : $n) : null; @endphp
                        @if ($waUrl)
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Hubungi via WA</a>
                        @endif
                        <form method="POST" action="{{ route('registrations.accept', $reg) }}" data-confirm="Terima pendaftaran ini dan buat murid baru?">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Terima &amp; Buat Murid</button>
                        </form>
                        <form method="POST" action="{{ route('registrations.reject', $reg) }}" data-confirm="Tolak pendaftaran ini?">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Tolak</button>
                        </form>
                    @elseif ($reg->status === \App\Models\Registration::STATUS_ACCEPTED && $reg->student)
                        <a href="{{ route('students.show', $reg->student) }}" class="text-sm font-medium text-sky-700 hover:underline">Lihat murid &rarr;</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-10 text-center text-sm text-slate-500 shadow-sm">Belum ada pendaftaran masuk.</div>
        @endforelse
    </div>
</x-app-layout>
