@php($game = $game ?? null)
@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="tema" value="Tema Game" />
        <x-text-input id="tema" name="tema" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('tema', $game->tema ?? '')" placeholder="mis. Animals Vocabulary" required />
        <x-input-error :messages="$errors->get('tema')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="link" value="Link Game (Bamboozle, dll)" />
        <x-text-input id="link" name="link" type="url" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('link', $game->link ?? '')" placeholder="https://www.baamboozle.com/game/..." required />
        <x-input-error :messages="$errors->get('link')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900" @checked(old('is_active', $game->is_active ?? true))>
            <div>
                <p class="font-medium text-slate-900">Game Aktif</p>
                <p class="text-sm text-slate-500">Hanya game aktif yang muncul di pilihan saat guru absen.</p>
            </div>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('games.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batal</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        {{ $submitLabel }}
    </x-primary-button>
</div>
