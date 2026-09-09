<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Feedback Murid</h2>
            <p class="text-sm text-slate-500">Pesan, kesan &amp; saran yang dikirim murid lewat form publik.</p>
        </div>
    </x-slot>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total Feedback</p>
            <p class="mt-2 text-4xl font-semibold text-slate-900">{{ $feedbacks->count() }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Rata-rata Rating</p>
            <p class="mt-2 text-4xl font-semibold text-emerald-600">{{ $average !== null ? $average.' / 5' : '-' }}</p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($feedbacks as $feedback)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-slate-900">{{ $feedback->student?->name ?? 'Murid' }}</span>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $feedback->rating }} / 5</span>
                        @if ($feedback->allow_testimonial)
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Boleh jadi testimoni</span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-400">{{ $feedback->created_at?->locale('id')->translatedFormat('d M Y') }}</span>
                </div>
                <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $feedback->message }}</p>
                @if ($feedback->suggestion)
                    <div class="mt-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Saran</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $feedback->suggestion }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-10 text-center text-sm text-slate-500 shadow-sm">Belum ada feedback yang masuk.</div>
        @endforelse
    </div>
</x-app-layout>
