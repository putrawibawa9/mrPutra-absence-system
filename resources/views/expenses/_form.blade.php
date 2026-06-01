@php($expense = $expense ?? null)
@csrf

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="expense_category_id" value="Expense Category" />
        <select id="expense_category_id" name="expense_category_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="">Select category</option>
            @foreach ($expenseCategories as $expenseCategory)
                <option value="{{ $expenseCategory->id }}" @selected(old('expense_category_id', $expense->expense_category_id ?? '') == $expenseCategory->id)>{{ $expenseCategory->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('expense_category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="expense_date" value="Expense Date" />
        <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString())" required />
        <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="title" value="Expense Title" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('title', $expense->title ?? '')" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" value="Amount" />
        <x-text-input id="amount" name="amount" type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('amount', $expense->amount ?? '')" required />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notes (optional)" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes', $expense->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('expenses.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        {{ $submitLabel }}
    </x-primary-button>
</div>
