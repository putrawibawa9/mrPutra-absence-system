@php($learning_module = $learning_module ?? null)
@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Module Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('name', $learning_module->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="price" value="Price" />
        <x-text-input id="price" name="price" type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('price', $learning_module->price ?? '')" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notes (optional)" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes', $learning_module->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900" @checked(old('is_active', $learning_module->is_active ?? true))>
            <div>
                <p class="font-medium text-slate-900">Active Module</p>
                <p class="text-sm text-slate-500">Module aktif akan muncul otomatis di form payment.</p>
            </div>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('learning-modules.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        {{ $submitLabel }}
    </x-primary-button>
</div>
