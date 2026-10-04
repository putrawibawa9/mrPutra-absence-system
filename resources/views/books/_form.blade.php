@php($book = $book ?? null)
@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="title" value="Judul Buku" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('title', $book->title ?? '')" placeholder="mis. English File Elementary" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Catatan (opsional)" />
        <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('notes', $book->notes ?? '')" placeholder="mis. edisi 4 / level A2" />
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900" @checked(old('is_active', $book->is_active ?? true))>
            <div>
                <p class="font-medium text-slate-900">Buku Aktif</p>
                <p class="text-sm text-slate-500">Hanya buku aktif yang muncul di dropdown saat memilih buku kelas.</p>
            </div>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('books.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batal</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        {{ $submitLabel }}
    </x-primary-button>
</div>
