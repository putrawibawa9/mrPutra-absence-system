<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Buat Komitmen / Cicilan</h2>
        <p class="text-sm text-slate-500">Pecah biaya besar/tahunan jadi cicilan bulanan otomatis. Item &amp; kategori sama untuk seluruh cicilan.</p>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('expense-commitments.store') }}" data-confirm="Buat komitmen dan generate seluruh cicilannya?">
            @csrf
            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="expense_item_id" value="Item" />
                    <select id="expense_item_id" name="expense_item_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
                        <option value="">Pilih item</option>
                        @foreach ($expenseItems as $it)
                            <option value="{{ $it->id }}" @selected(old('expense_item_id') == $it->id)>{{ $it->name }} — {{ $it->category?->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('expense_item_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="name" value="Nama Komitmen" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('name')" placeholder="mis. CapCut Pro 1 Tahun" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="start_date" value="Mulai Tanggal" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('start_date', now()->toDateString())" required />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="total_amount" value="Total (Rp)" />
                    <x-text-input id="total_amount" name="total_amount" type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('total_amount')" required />
                    <x-input-error :messages="$errors->get('total_amount')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="months" value="Jumlah Bulan" />
                    <x-text-input id="months" name="months" type="number" min="2" max="60" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('months', 12)" required />
                    <p class="mt-1 text-xs text-slate-500">Selisih pembulatan ditaruh di cicilan terakhir.</p>
                    <x-input-error :messages="$errors->get('months')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="notes" value="Notes (opsional)" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('expense-commitments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 sm:w-auto">Buat &amp; Generate Cicilan</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
