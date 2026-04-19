<x-app-layout>
    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (string) $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
            'email' => $student->email,
        ])->values();
        $selectedStudentData = old('student_id') ? $students->firstWhere('id', old('student_id')) : null;
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Payment</h2>
    </x-slot>

    <div
        class="rounded-3xl bg-white p-6 shadow-sm"
        x-data="{
            sourceType: '{{ old('source_type', 'package') }}',
            studentSearch: '',
            selectedStudentId: '{{ old('student_id') }}',
            selectedPackageId: '{{ old('package_id') }}',
            bookPrice: '{{ old('book_price') }}',
            students: @js($studentOptions),
            packages: @js($packages->mapWithKeys(fn ($package) => [
                $package->id => [
                    'price' => $package->price,
                    'label' => $package->name,
                ],
            ])),
            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID').format(amount ?? 0);
            },
            get selectedStudent() {
                return this.students.find((student) => student.id === this.selectedStudentId) ?? null;
            },
            get filteredStudents() {
                const query = this.studentSearch.trim().toLowerCase();

                if (! query) {
                    return this.students.slice(0, 12);
                }

                return this.students
                    .filter((student) => `${student.name} ${student.phone} ${student.email ?? ''}`.toLowerCase().includes(query))
                    .slice(0, 12);
            },
            selectStudent(studentId) {
                this.selectedStudentId = studentId;
            },
            get selectedPackage() {
                return this.packages[this.selectedPackageId] ?? null;
            },
        }"
    >
        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="grid gap-6 md:grid-cols-2" data-confirm="Save this payment and generate the receipt? Please double check the student and nominal first.">
            @csrf

            <div class="md:col-span-2">
                <x-input-label for="student_search" value="Search Student" />
                <input type="hidden" name="student_id" x-model="selectedStudentId">
                <x-text-input
                    id="student_search"
                    type="text"
                    x-model="studentSearch"
                    class="mt-1 block w-full rounded-xl border-slate-300"
                    placeholder="Search by student name, phone, or email"
                    autocomplete="off"
                />
                <div class="mt-3 max-h-72 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <template x-for="student in filteredStudents" :key="student.id">
                        <button
                            type="button"
                            @click="selectStudent(student.id)"
                            class="flex w-full items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900" x-text="student.name"></p>
                                <p class="mt-1 text-sm text-slate-500" x-text="student.phone"></p>
                                <p class="mt-1 text-xs text-slate-400" x-text="student.email || '-'"></p>
                            </div>
                            <span
                                x-show="selectedStudentId === student.id"
                                class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            >
                                Selected
                            </span>
                        </button>
                    </template>

                    <p x-show="filteredStudents.length === 0" class="rounded-2xl bg-white px-4 py-5 text-sm text-slate-500">
                        No student found. Try another keyword.
                    </p>
                </div>

                <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <template x-if="selectedStudent">
                        <div>
                            <p class="font-medium text-slate-900" x-text="selectedStudent.name"></p>
                            <p class="mt-1 text-sm text-slate-500" x-text="selectedStudent.phone"></p>
                            <p class="mt-1 text-xs text-slate-400" x-text="selectedStudent.email || '-'"></p>
                        </div>
                    </template>
                    <template x-if="! selectedStudent">
                        <p class="text-sm text-amber-700">Please search and select a student first.</p>
                    </template>
                </div>
                <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Only active students can receive new payments.</p>
            </div>

            <div>
                <x-input-label for="source_type" value="Input Type" />
                <select id="source_type" name="source_type" x-model="sourceType" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="package">Package Payment</option>
                    <option value="book">Book / Module Payment</option>
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
                <select id="package_id" name="package_id" x-model="selectedPackageId" x-bind:disabled="sourceType !== 'package'" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">Select package</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected(old('package_id') == $package->id)>{{ $package->name }} ({{ $package->total_sessions }} sessions) - Rp {{ number_format($package->price, 0, ',', '.') }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('package_id')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'package'" x-cloak>
                <x-input-label for="initial_paid_amount" value="Paid Now" />
                <x-text-input id="initial_paid_amount" name="initial_paid_amount" type="number" min="0" x-bind:disabled="sourceType !== 'package'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('initial_paid_amount')" placeholder="Leave empty to mark as fully paid" />
                <x-input-error :messages="$errors->get('initial_paid_amount')" class="mt-2" />
                <template x-if="selectedPackage">
                    <p class="mt-2 text-xs text-slate-500">
                        Package price: Rp <span x-text="formatCurrency(selectedPackage.price)"></span>. Leave blank to mark full payment immediately.
                    </p>
                </template>
            </div>

            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="book_title" value="Book / Module Name" />
                <x-text-input id="book_title" name="book_title" type="text" x-bind:disabled="sourceType !== 'book'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('book_title')" placeholder="Example: Module IELTS Speaking" />
                <x-input-error :messages="$errors->get('book_title')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="book_price" value="Book / Module Price" />
                <x-text-input id="book_price" name="book_price" type="number" min="1" x-model="bookPrice" x-bind:disabled="sourceType !== 'book'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('book_price')" placeholder="Example: 150000" />
                <x-input-error :messages="$errors->get('book_price')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="initial_paid_amount_book" value="Paid Now" />
                <x-text-input id="initial_paid_amount_book" name="initial_paid_amount" type="number" min="0" x-bind:disabled="sourceType !== 'book'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('initial_paid_amount')" placeholder="Leave empty to mark as fully paid" />
                <x-input-error :messages="$errors->get('initial_paid_amount')" class="mt-2" />
                <template x-if="bookPrice">
                    <p class="mt-2 text-xs text-slate-500">
                        Book or module price: Rp <span x-text="formatCurrency(bookPrice)"></span>. Leave blank to mark full payment immediately.
                    </p>
                </template>
            </div>

            <div x-show="sourceType === 'manual'" x-cloak>
                <x-input-label for="manual_total_sessions" value="Manual Total Sessions" />
                <x-text-input id="manual_total_sessions" name="manual_total_sessions" type="number" min="1" x-bind:disabled="sourceType !== 'manual'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('manual_total_sessions')" />
                <x-input-error :messages="$errors->get('manual_total_sessions')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'manual'" x-cloak>
                <x-input-label for="manual_remaining_sessions" value="Manual Remaining Sessions" />
                <x-text-input id="manual_remaining_sessions" name="manual_remaining_sessions" type="number" min="0" x-bind:disabled="sourceType !== 'manual'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('manual_remaining_sessions')" />
                <x-input-error :messages="$errors->get('manual_remaining_sessions')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Use this for migration from Excel when only the current token balance is known.</p>
            </div>

            <div x-show="sourceType === 'manual'" x-cloak>
                <x-input-label for="manual_price" value="Manual Price" />
                <x-text-input id="manual_price" name="manual_price" type="number" min="0" x-bind:disabled="sourceType !== 'manual'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('manual_price')" placeholder="Example: 750000" />
                <x-input-error :messages="$errors->get('manual_price')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Harga ini akan dicatat sebagai opening balance yang sudah lunas, lalu tampil di receipt.</p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="notes" value="Notes (optional)" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm">
                <p class="font-medium text-slate-900">E-Receipt Signature</p>
                <p class="mt-2 text-slate-600">
                    Receipt akan otomatis memakai tanda tangan dari profil akun kamu.
                    <a href="{{ route('profile.edit') }}" class="font-medium text-slate-900 underline">Atur tanda tangan di profile</a>.
                </p>
                @if (auth()->user()->signatureUrl())
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current Signature</p>
                        <img src="{{ auth()->user()->signatureUrl() }}" alt="Current signature" class="max-h-24 w-auto rounded-xl border border-slate-200 bg-white p-2">
                    </div>
                @else
                    <p class="mt-3 text-amber-700">Belum ada tanda tangan di profile. Receipt tetap bisa dibuat, tapi area tanda tangan akan kosong.</p>
                @endif
            </div>

            <div class="md:col-span-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('payments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                <button
                    type="submit"
                    class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto"
                    x-bind:disabled="! selectedStudentId"
                >
                    Save Payment
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
