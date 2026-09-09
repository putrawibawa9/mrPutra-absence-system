<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Murid Non-aktif</h2>
            <p class="text-sm text-slate-500">Murid yang sudah keluar. Minta pesan, kesan & saran untuk Mr. Putra Speak lewat WA.</p>
        </div>
    </x-slot>

    <div class="rounded-3xl bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Minta Feedback Alumni</h3>
                <p class="text-sm text-slate-500">Pesan otomatis menanyakan pesan, kesan, dan saran mereka.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $students->count() }} murid</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Murid</th>
                        <th class="px-6 py-3 font-medium">No. HP</th>
                        <th class="px-6 py-3 font-medium">Keluar Sejak</th>
                        <th class="px-6 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <a href="{{ route('students.show', $student) }}" class="hover:underline">{{ $student->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->phone ?: '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->deactivated_at?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                @if (($student->feedbacks_count ?? 0) > 0)
                                    <span class="mr-2 inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sudah kirim feedback</span>
                                @endif
                                @php $waUrl = $student->feedbackRequestWhatsAppUrl(); @endphp
                                @if ($waUrl)
                                    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                        Minta Feedback via WA
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">No. HP kosong</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">Belum ada murid non-aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
