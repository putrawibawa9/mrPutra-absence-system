<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Template Pesan</h2>
            <p class="text-sm text-slate-500">Edit isi pesan WhatsApp (tagih pembayaran, follow-up, feedback, murid selesai). Placeholder dalam kurung kurawal akan otomatis terisi data murid saat dikirim.</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="space-y-5">
        @foreach ($templates as $tpl)
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $tpl->name }}</h3>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tpl->is_custom ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-500' }}">{{ $tpl->is_custom ? 'Disesuaikan' : 'Default' }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($tpl->placeholders as $ph)
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600" style="font-family:monospace">{{ $ph }}</span>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('message-templates.update', $tpl->key) }}" class="mt-3">
                    @csrf
                    @method('PUT')
                    <textarea name="body" rows="9" class="block w-full rounded-xl border-slate-300 text-sm" style="font-family:inherit">{{ old('body', $tpl->body) }}</textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    <div class="mt-3">
                        <button type="submit" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Simpan</button>
                    </div>
                </form>
                @if ($tpl->is_custom)
                    <form method="POST" action="{{ route('message-templates.reset', $tpl->key) }}" class="mt-2" data-confirm="Kembalikan template ini ke teks default?">
                        @csrf
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Kembalikan ke default</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
