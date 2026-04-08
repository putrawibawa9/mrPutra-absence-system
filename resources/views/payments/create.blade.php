<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Payment</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm" x-data="{ sourceType: '{{ old('source_type', 'package') }}' }">
        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="grid gap-6 md:grid-cols-2">
            @csrf

            <div>
                <x-input-label for="student_id" value="Student" />
                <select id="student_id" name="student_id" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">Select student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Only active students can receive new payments.</p>
            </div>

            <div>
                <x-input-label for="source_type" value="Input Type" />
                <select id="source_type" name="source_type" x-model="sourceType" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="package">Package Payment</option>
                    <option value="manual">Manual Opening Balance</option>
                </select>
                <x-input-error :messages="$errors->get('source_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="payment_date" value="Payment Date" />
                <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('payment_date', now()->toDateString())" required />
                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'package'" x-cloak>
                <x-input-label for="package_id" value="Package" />
                <select id="package_id" name="package_id" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">Select package</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected(old('package_id') == $package->id)>{{ $package->name }} ({{ $package->total_sessions }} sessions)</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('package_id')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'manual'" x-cloak>
                <x-input-label for="manual_total_sessions" value="Manual Total Sessions" />
                <x-text-input id="manual_total_sessions" name="manual_total_sessions" type="number" min="1" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('manual_total_sessions')" />
                <x-input-error :messages="$errors->get('manual_total_sessions')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'manual'" x-cloak>
                <x-input-label for="manual_remaining_sessions" value="Manual Remaining Sessions" />
                <x-text-input id="manual_remaining_sessions" name="manual_remaining_sessions" type="number" min="0" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('manual_remaining_sessions')" />
                <x-input-error :messages="$errors->get('manual_remaining_sessions')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Use this for migration from Excel when only the current token balance is known.</p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="notes" value="Notes (optional)" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="signature" value="Signature (optional)" />
                <input id="signature" name="signature" type="file" accept=".png,.jpg,.jpeg" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                <x-input-error :messages="$errors->get('signature')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Upload your signature image to show it on the e-receipt.</p>
            </div>

            <div class="md:col-span-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('payments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
                    Save Payment
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
