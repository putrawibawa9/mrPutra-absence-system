@php($expense_item = $expense_item ?? null)
@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nama Item" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('name', $expense_item->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="expense_category_id" value="Kategori" />
        <select id="expense_category_id" name="expense_category_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('expense_category_id', $expense_item->expense_category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('expense_category_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="keywords" value="Keywords (pisahkan dengan koma)" />
        <x-text-input id="keywords" name="keywords" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('keywords', $expense_item->keywords ?? '')" placeholder="mis. hostinger, hosting, server" />
        <p class="mt-1 text-xs text-slate-500">Dipakai untuk peringatan salah item & pencocokan otomatis saat migrasi data.</p>
        <x-input-error :messages="$errors->get('keywords')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="default_amount" value="Nominal default (opsional)" />
        <x-text-input id="default_amount" name="default_amount" type="number" min="0" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('default_amount', $expense_item->default_amount ?? '')" />
        <x-input-error :messages="$errors->get('default_amount')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900" @checked(old('is_active', $expense_item->is_active ?? true))>
            <div>
                <p class="font-medium text-slate-900">Item Aktif</p>
                <p class="text-sm text-slate-500">Hanya item aktif yang muncul di form expense.</p>
            </div>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('expense-items.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        {{ $submitLabel }}
    </x-primary-button>
</div>
