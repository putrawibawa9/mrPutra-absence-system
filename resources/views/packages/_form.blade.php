<div class="grid gap-6 md:grid-cols-3">
    <div class="md:col-span-3">
        <x-input-label for="name" value="Package Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('name', $package->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="total_sessions" value="Total Sessions" />
        <x-text-input id="total_sessions" name="total_sessions" type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('total_sessions', $package->total_sessions ?? '')" required />
        <x-input-error :messages="$errors->get('total_sessions')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="price" value="Price" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('price', $package->price ?? '')" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('packages.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
    <x-primary-button class="bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950">
        Save Package
    </x-primary-button>
</div>
