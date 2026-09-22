@php($expense = $expense ?? null)
@php($isGrouped = $expense && $expense->amortization_group)
@csrf

@if (session('expense_warnings'))
    <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <p class="font-semibold">Perlu dicek dulu (tidak wajib):</p>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            @foreach (session('expense_warnings') as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs">Kalau memang sudah benar, klik tombol simpan lagi untuk melanjutkan.</p>
    </div>
@endif

{{-- confirmed=1 setelah ada peringatan, supaya submit berikutnya lolos tanpa blokir --}}
<input type="hidden" name="confirmed" value="{{ session('expense_warnings') ? 1 : 0 }}">

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="expense_item_id" value="Item Expense *" />
        @if ($isGrouped)
            <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700">
                {{ $expense->item?->name ?? '-' }}
                <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs">baris cicilan</span>
            </div>
            <input type="hidden" name="expense_item_id" value="{{ $expense->expense_item_id }}">
            <p class="mt-1 text-xs text-amber-700">Ini baris cicilan. Item &amp; kategori dikunci — ubah lewat menu Komitmen (berlaku untuk seluruh grup).</p>
        @else
            <input type="text" id="item-filter" placeholder="Ketik untuk mencari item…" class="mt-1 block w-full rounded-xl border-slate-300" autocomplete="off" />
            <select id="expense_item_id" name="expense_item_id" class="mt-2 block w-full rounded-xl border-slate-300" required>
                <option value="">Pilih item</option>
                @foreach ($expenseItems as $it)
                    <option value="{{ $it->id }}"
                        data-category="{{ $it->category?->name }}"
                        data-behavior="{{ $it->category?->cost_behavior }}"
                        data-default="{{ $it->default_amount }}"
                        @selected(old('expense_item_id', $expense->expense_item_id ?? '') == $it->id)>
                        {{ $it->name }} — {{ $it->category?->name }}
                    </option>
                @endforeach
            </select>
        @endif
        <x-input-error :messages="$errors->get('expense_item_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category_display" value="Kategori (otomatis dari item)" />
        <input id="category_display" type="text" readonly value="{{ $expense?->category?->name }}" class="mt-1 block w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-slate-600" />
    </div>

    <div>
        <x-input-label for="expense_date" value="Tanggal" />
        <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString())" required />
        <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" value="Jumlah (Rp)" />
        <x-text-input id="amount" name="amount" type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('amount', $expense->amount ?? '')" required />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="title" value="Keterangan (opsional)" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('title', $expense->title ?? '')" placeholder="Kosongkan untuk pakai nama item" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notes (opsional)" />
        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes', $expense->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('expenses.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        {{ $submitLabel }}
    </x-primary-button>
</div>

@unless ($isGrouped)
    <script>
        (function () {
            const select = document.getElementById('expense_item_id');
            const catDisplay = document.getElementById('category_display');
            const amount = document.getElementById('amount');
            const filter = document.getElementById('item-filter');

            function syncCategory() {
                const opt = select.options[select.selectedIndex];
                if (!opt) return;
                catDisplay.value = opt.getAttribute('data-category') || '';
                const def = opt.getAttribute('data-default');
                if (def && amount && !amount.value) amount.value = def;
            }

            if (select) {
                select.addEventListener('change', syncCategory);
                syncCategory();
            }

            if (filter && select) {
                filter.addEventListener('input', function () {
                    const q = filter.value.toLowerCase();
                    for (const opt of select.options) {
                        if (!opt.value) continue;
                        opt.hidden = q !== '' && !opt.text.toLowerCase().includes(q);
                    }
                });
            }
        })();
    </script>
@endunless
