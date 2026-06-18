<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Kelas</h2>
                <p class="text-sm text-slate-500">Simpan daftar murid sekali, lalu absen tinggal pilih kelas & centang yang hadir.</p>
            </div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('classrooms.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">+ Buat Kelas</a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @forelse ($classrooms as $classroom)
            <div class="rounded-3xl bg-white p-6 shadow-sm {{ $classroom->is_active ? '' : 'opacity-60' }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $classroom->name }}</h3>
                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $classroom->divisionLabel() }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $classroom->isPrivate() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ $classroom->formatLabel() }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $classroom->ageLabel() }}</span>
                            @unless ($classroom->is_active)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Nonaktif</span>
                            @endunless
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $classroom->students_count }} murid</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $classroom->students->pluck('name')->join(', ') ?: 'Belum ada murid' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($classroom->is_active)
                            <a href="{{ route('classrooms.attendances.create', $classroom) }}" class="inline-flex justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Absen Kelas</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('classrooms.edit', $classroom) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Edit</a>
                            <form method="POST" action="{{ route('classrooms.toggle-status', $classroom) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">{{ $classroom->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}" data-confirm="Hapus kelas ini? Riwayat absensi tidak akan terhapus.">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Belum ada kelas. {{ auth()->user()->isAdmin() ? 'Klik "Buat Kelas" untuk mulai.' : '' }}
            </div>
        @endforelse
    </div>
</x-app-layout>
