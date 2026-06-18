<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="title" value="Judul Materi" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('title', $materialLink->title ?? '')" placeholder="Contoh: Daily Conversation - Unit 1" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="url" value="Link Materi" />
        <x-text-input id="url" name="url" type="url" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('url', $materialLink->url ?? '')" placeholder="https://..." required />
        <p class="mt-2 text-xs text-slate-500">Saat link ini diklik, user akan langsung diarahkan ke materi tersebut.</p>
        <x-input-error :messages="$errors->get('url')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" value="Deskripsi (opsional)" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-2xl border-slate-300">{{ old('description', $materialLink->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="is_active" value="Status" />
        <select id="is_active" name="is_active" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="1" @selected(old('is_active', isset($materialLink) ? (int) $materialLink->is_active : 1) == 1)>Aktif</option>
            <option value="0" @selected(old('is_active', isset($materialLink) ? (int) $materialLink->is_active : 1) == 0)>Nonaktif</option>
        </select>
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('material-links.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batal</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        Simpan Link Materi
    </x-primary-button>
</div>
