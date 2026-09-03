<x-app-layout>
    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (string) $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
            'email' => $student->email,
            'remaining' => (int) ($student->payments_sum_remaining_sessions ?? 0),
            'net' => (int) ($student->payments_sum_remaining_sessions ?? 0) - (int) ($student->token_debt_count ?? 0),
        ])->values();
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Tambah Pembayaran</h2>
    </x-slot>

    <div
        class="rounded-3xl bg-white p-6 shadow-sm"
        x-data="{
            sourceType: '{{ old('source_type', 'token') }}',
            studentSearch: '',
            selectedStudentId: '{{ old('student_id') }}',
            totalSessions: '{{ old('total_sessions') }}',
            priceAmount: '{{ old('price_amount') }}',
            selectedLearningModuleId: '{{ old('learning_module_id') }}',
            bookPrice: '{{ old('book_price') }}',
            students: @js($studentOptions),
            learningModules: @js($learningModules->mapWithKeys(fn ($module) => [
                $module->id => [
                    'name' => $module->name,
                    'price' => $module->price,
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
            get pricePerSession() {
                const tokens = parseInt(this.totalSessions) || 0;
                const price = parseInt(this.priceAmount) || 0;

                if (tokens <= 0 || price <= 0) {
                    return 0;
                }

                return Math.floor(price / tokens);
            },
            get selectedLearningModule() {
                return this.learningModules[this.selectedLearningModuleId] ?? null;
            },
        }"
    >
        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="grid gap-6 md:grid-cols-2" data-confirm="Simpan pembayaran ini dan buat kwitansi? Cek dulu murid dan nominalnya.">
            @csrf

            <div class="md:col-span-2">
                <x-input-label for="student_search" value="Cari Murid" />
                <input type="hidden" name="student_id" x-model="selectedStudentId">
                <x-text-input
                    id="student_search"
                    type="text"
                    x-model="studentSearch"
                    class="mt-1 block w-full rounded-xl border-slate-300"
                    placeholder="Cari nama, nomor HP, atau email"
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
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="student.net > 0 ? 'bg-emerald-50 text-emerald-700' : (student.net < 0 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700')"
                                    x-text="student.net + ' token'"
                                ></span>
                                <span
                                    x-show="selectedStudentId === student.id"
                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                >
                                    Dipilih
                                </span>
                            </div>
                        </button>
                    </template>

                    <p x-show="filteredStudents.length === 0" class="rounded-2xl bg-white px-4 py-5 text-sm text-slate-500">
                        Murid tidak ditemukan.
                    </p>
                </div>

                <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <template x-if="selectedStudent">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900" x-text="selectedStudent.name"></p>
                                <p class="mt-1 text-sm text-slate-500" x-text="selectedStudent.phone"></p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="selectedStudent.net > 0 ? 'bg-emerald-50 text-emerald-700' : (selectedStudent.net < 0 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700')"
                                x-text="'Saldo ' + selectedStudent.net + ' token'"
                            ></span>
                        </div>
                    </template>
                    <template x-if="! selectedStudent">
                        <p class="text-sm text-amber-700">Pilih murid dulu.</p>
                    </template>
                </div>
                <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="source_type" value="Jenis Pembayaran" />
                <select id="source_type" name="source_type" x-model="sourceType" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="token">Token Kelas</option>
                    <option value="book">Buku / Modul</option>
                </select>
                <x-input-error :messages="$errors->get('source_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="payment_date" value="Tanggal Bayar" />
                <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('payment_date', now()->toDateString())" required />
                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
            </div>

            {{-- ===== Token kelas ===== --}}
            <div x-show="sourceType === 'token'" x-cloak>
                <x-input-label for="division" value="Divisi (scope token, opsional)" />
                <select id="division" name="division" x-bind:disabled="sourceType !== 'token'" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">— Semua (umum) —</option>
                    @foreach (\App\Models\Classroom::divisionOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('division') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('division')" class="mt-2" />
                <p class="mt-1 text-xs text-slate-500">Kosongkan untuk token umum; isi agar token hanya bisa dipakai di divisi + format tertentu.</p>
            </div>

            <div x-show="sourceType === 'token'" x-cloak>
                <x-input-label for="format" value="Format (scope token, opsional)" />
                <select id="format" name="format" x-bind:disabled="sourceType !== 'token'" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">— Semua (umum) —</option>
                    @foreach (\App\Models\Classroom::formatOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('format') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('format')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'token'" x-cloak>
                <x-input-label for="total_sessions" value="Jumlah Token (sesi)" />
                <x-text-input id="total_sessions" name="total_sessions" type="number" min="1" x-model="totalSessions" x-bind:disabled="sourceType !== 'token'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('total_sessions')" placeholder="Contoh: 8" />
                <x-input-error :messages="$errors->get('total_sessions')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'token'" x-cloak>
                <x-input-label for="price_amount" value="Harga Total" />
                <x-text-input id="price_amount" name="price_amount" type="number" min="0" x-model="priceAmount" x-bind:disabled="sourceType !== 'token'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('price_amount')" placeholder="Contoh: 800000" />
                <x-input-error :messages="$errors->get('price_amount')" class="mt-2" />
                <template x-if="pricePerSession > 0">
                    <p class="mt-2 text-xs text-slate-500">
                        Harga per sesi: Rp <span x-text="formatCurrency(pricePerSession)"></span>
                    </p>
                </template>
            </div>

            <div x-show="sourceType === 'token'" x-cloak class="md:col-span-2">
                <x-input-label for="initial_paid_amount" value="Dibayar Sekarang (opsional)" />
                <x-text-input id="initial_paid_amount" name="initial_paid_amount" type="number" min="0" x-bind:disabled="sourceType !== 'token'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('initial_paid_amount')" placeholder="Kosongkan jika lunas penuh" />
                <x-input-error :messages="$errors->get('initial_paid_amount')" class="mt-2" />
                <p class="mt-2 text-xs text-slate-500">Kosongkan untuk langsung lunas. Isi sebagian jika bayar cicilan.</p>
            </div>

            {{-- ===== Buku / Modul ===== --}}
            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="learning_module_id" value="Modul Tersimpan (opsional)" />
                <select id="learning_module_id" name="learning_module_id" x-model="selectedLearningModuleId" x-bind:disabled="sourceType !== 'book'" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">Pilih modul tersimpan</option>
                    @foreach ($learningModules as $learningModule)
                        <option value="{{ $learningModule->id }}" @selected(old('learning_module_id') == $learningModule->id)>{{ $learningModule->name }} - Rp {{ number_format($learningModule->price, 0, ',', '.') }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('learning_module_id')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="book_title" value="Nama Buku / Modul" />
                <x-text-input id="book_title" name="book_title" type="text" x-bind:disabled="sourceType !== 'book' || !! selectedLearningModuleId" x-bind:value="selectedLearningModule ? selectedLearningModule.name : '{{ old('book_title') }}'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('book_title')" placeholder="Contoh: Modul IELTS Speaking" />
                <x-input-error :messages="$errors->get('book_title')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="book_price" value="Harga Buku / Modul" />
                <x-text-input id="book_price" name="book_price" type="number" min="1" x-model="bookPrice" x-bind:disabled="sourceType !== 'book' || !! selectedLearningModuleId" x-bind:value="selectedLearningModule ? selectedLearningModule.price : bookPrice" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('book_price')" placeholder="Contoh: 150000" />
                <x-input-error :messages="$errors->get('book_price')" class="mt-2" />
            </div>

            <div x-show="sourceType === 'book'" x-cloak>
                <x-input-label for="initial_paid_amount_book" value="Dibayar Sekarang (opsional)" />
                <x-text-input id="initial_paid_amount_book" name="initial_paid_amount" type="number" min="0" x-bind:disabled="sourceType !== 'book'" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('initial_paid_amount')" placeholder="Kosongkan jika lunas penuh" />
                <x-input-error :messages="$errors->get('initial_paid_amount')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="notes" value="Catatan (opsional)" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm">
                <p class="font-medium text-slate-900">Tanda Tangan Kwitansi</p>
                <p class="mt-2 text-slate-600">
                    Kwitansi otomatis memakai tanda tangan dari profil akunmu.
                    <a href="{{ route('profile.edit') }}" class="font-medium text-slate-900 underline">Atur di profile</a>.
                </p>
                @if (auth()->user()->signatureUrl())
                    <div class="mt-4">
                        <img src="{{ auth()->user()->signatureUrl() }}" alt="Tanda tangan" class="max-h-24 w-auto rounded-xl border border-slate-200 bg-white p-2">
                    </div>
                @else
                    <p class="mt-3 text-amber-700">Belum ada tanda tangan di profile. Kwitansi tetap bisa dibuat, area tanda tangan kosong.</p>
                @endif
            </div>

            <div class="md:col-span-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('payments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batal</a>
                <button
                    type="submit"
                    class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto"
                    x-bind:disabled="! selectedStudentId"
                >
                    Simpan Pembayaran
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
