<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Attendance</h2>
    </x-slot>

    <div x-data="{ mode: '{{ old('mode', 'single') }}' }" class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Attendance Mode</h3>
            <p class="mt-1 text-sm text-slate-500">Use single mode for one student, or group mode when you teach one class with several students present.</p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                    <input type="radio" name="mode_picker" value="single" x-model="mode" class="mt-1 border-slate-300 text-slate-900">
                    <div>
                        <p class="font-medium text-slate-900">Single Student</p>
                        <p class="text-sm text-slate-500">Choose one student and one active payment manually.</p>
                    </div>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                    <input type="radio" name="mode_picker" value="group" x-model="mode" class="mt-1 border-slate-300 text-slate-900">
                    <div>
                        <p class="font-medium text-slate-900">Group / Class</p>
                        <p class="text-sm text-slate-500">Create one session and mark all students who are present.</p>
                    </div>
                </label>
            </div>
        </div>

        <div x-show="mode === 'single'" x-cloak class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Choose Student</h3>
                <p class="mt-1 text-sm text-slate-500">After choosing a student, the latest active payment is selected automatically.</p>

                <form method="GET" action="{{ route('attendances.create') }}" class="mt-6">
                    <x-input-label for="student_id" value="Student" />
                    <select id="student_id" name="student_id" onchange="this.form.submit()" class="mt-1 block w-full rounded-xl border-slate-300">
                        <option value="">Select student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected((string) $selectedStudent === (string) $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Inactive students are hidden from attendance input.</p>
                </form>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('attendances.store') }}">
                    @csrf
                    <input type="hidden" name="mode" value="single">

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="student_id_post" value="Student" />
                            <select id="student_id_post" name="student_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
                                <option value="">Select student</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id', $selectedStudent) == $student->id)>{{ $student->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="payment_id" value="Active Payment" />
                            <select id="payment_id" name="payment_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
                                <option value="">Select payment</option>
                                @foreach ($activePayments as $payment)
                                    <option value="{{ $payment->id }}" @selected(old('payment_id', $selectedPaymentId) == $payment->id)>
                                        {{ $payment->displayLabel() }} - {{ $payment->remaining_sessions }} sessions left
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('payment_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date" value="Attendance Date" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="notes" value="Notes (optional)" />
                            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('mode', 'single') === 'single' ? old('notes') : '' }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    @if ($selectedStudent && $activePayments->isEmpty())
                        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            This student does not have an active payment. Create a payment first before saving attendance.
                        </div>
                    @endif

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('attendances.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                        <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto" :disabled="$selectedStudent && $activePayments->isEmpty()">
                            Save Attendance
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="mode === 'group'" x-cloak class="rounded-3xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('attendances.store') }}">
                @csrf
                <input type="hidden" name="mode" value="group">

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <x-input-label for="group_title" value="Class / Session Name" />
                        <x-text-input id="group_title" name="group_title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('group_title')" placeholder="Example: Group A - Evening Session" />
                        <x-input-error :messages="$errors->get('group_title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="group_date" value="Attendance Date" />
                        <x-text-input id="group_date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="group_notes" value="Notes (optional)" />
                        <textarea id="group_notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('mode') === 'group' ? old('notes') : '' }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Students Present</h3>
                            <p class="text-sm text-slate-500">Only active students with available sessions are shown as ready for group attendance.</p>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('student_ids')" class="mt-2" />

                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($students as $student)
                            @php
                                $activePayment = $student->latestActivePayment;
                                $canAttend = (bool) $activePayment && $activePayment->remaining_sessions > 0;
                            @endphp
                            <label class="flex items-start gap-3 rounded-2xl border p-4 {{ $canAttend ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50' }}">
                                <input
                                    type="checkbox"
                                    name="student_ids[]"
                                    value="{{ $student->id }}"
                                    class="mt-1 rounded border-slate-300 text-slate-900"
                                    @checked(in_array($student->id, old('student_ids', [])))
                                    @disabled(! $canAttend)
                                >
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900">{{ $student->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $student->phone }}</p>
                                    @if ($canAttend)
                                        <p class="mt-2 text-xs text-emerald-700">{{ $activePayment->displayLabel() }} - {{ $activePayment->remaining_sessions }} sessions left</p>
                                    @else
                                        <p class="mt-2 text-xs text-amber-700">No active payment available</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('attendances.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
                        Save Group Attendance
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
