<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Murid Sering Absen</h2>
            <p class="text-sm text-slate-500">Murid aktif yang tidak hadir {{ \App\Models\Student::CONSECUTIVE_ABSENCE_ALERT }}x berturut-turut atau lebih. Tanyakan kabarnya lewat WA.</p>
        </div>
    </x-slot>

    <div class="rounded-3xl bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">🚫 Perlu ditanyakan kabarnya</h3>
                <p class="text-sm text-slate-500">Absen berturut-turut dihitung sejak pertemuan terakhir murid hadir.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $rows->count() }} murid</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Murid</th>
                        <th class="px-6 py-3 font-medium">No. HP</th>
                        <th class="px-6 py-3 font-medium">Absen Berturut</th>
                        <th class="px-6 py-3 font-medium">Absen Terakhir</th>
                        <th class="px-6 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <a href="{{ route('students.show', $row->student) }}" class="hover:underline">{{ $row->student->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $row->student->phone ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ $row->streak }}x tidak hadir</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $row->last_absence?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                @if ($row->whatsapp_url)
                                    <a href="{{ $row->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                        Tanyakan via WA
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">No. HP kosong</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">Tidak ada murid yang absen berturut-turut. 🎉</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
