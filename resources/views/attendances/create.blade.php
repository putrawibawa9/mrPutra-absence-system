<x-app-layout>
    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (string) $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
            'book_info' => $student->book_info,
        ])->values();
        $selectedGroupTeacherIds = collect(old('group_teacher_ids', [auth()->id()]))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $selectedTeacherIds = collect(old('teacher_ids', [auth()->id()]))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $resolvedStudentId = old('student_id', $selectedStudent);
        $selectedStudentData = $resolvedStudentId ? $students->firstWhere('id', $resolvedStudentId) : null;
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Attendance</h2>
    </x-slot>

    <div
        x-data="attendanceCreatePage({
            mode: @js(old('mode', 'single')),
            students: @js($studentOptions),
            selectedStudentId: @js((string) ($resolvedStudentId ?: '')),
            createUrl: @js(route('attendances.create')),
        })"
        class="space-y-6"
    >
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Attendance Mode</h3>
            <p class="mt-1 text-sm text-slate-500">Use single mode for one student, or group mode when you teach one class with several students present.</p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                    <input type="radio" name="mode_picker" value="single" x-model="mode" class="mt-1 border-slate-300 text-slate-900">
                    <div>
                        <p class="font-medium text-slate-900">Single Student</p>
                        <p class="text-sm text-slate-500">Choose one student and the system will use the oldest active token balance automatically.</p>
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
                <p class="mt-1 text-sm text-slate-500">After choosing a student, all active tokens are treated as one balance. If no token is available, attendance can be recorded as token debt.</p>

                <div class="mt-6">
                    <x-input-label for="student_search_preview" value="Search Student" />
                    <x-text-input
                        id="student_search_preview"
                        type="text"
                        x-model="studentSearch"
                        class="mt-1 block w-full rounded-xl border-slate-300"
                        placeholder="Search by student name or phone"
                        autocomplete="off"
                    />
                    <p class="mt-2 text-xs text-slate-500">Inactive students are hidden from attendance input.</p>

                    <div class="mt-3 max-h-80 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <template x-for="student in filteredStudents" :key="student.id">
                            <button
                                type="button"
                                @click="openStudent(student.id)"
                                class="flex w-full items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900" x-text="student.name"></p>
                                    <p class="mt-1 text-sm text-slate-500" x-text="student.phone"></p>
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
                            No student found. Try another name or phone number.
                        </p>
                    </div>
                </div>

                @if ($selectedStudentData)
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Book Info</h4>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $selectedStudentData->book_info ?: 'No book info added for this student.' }}</p>
                    </div>
                @endif

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Previous Learning Journal</h4>
                    @if ($selectedPreviousAttendance)
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <p><span class="font-medium text-slate-900">Date:</span> {{ $selectedPreviousAttendance->date->format('d M Y') }}</p>
                            <p><span class="font-medium text-slate-900">Teacher:</span> {{ $selectedPreviousAttendance->teacher?->name ?? '-' }}</p>
                            @if ($selectedPreviousAttendance->batch)
                                <p><span class="font-medium text-slate-900">Session:</span> {{ $selectedPreviousAttendance->batch->title }}</p>
                            @endif
                            <p class="whitespace-pre-line rounded-xl bg-white p-3 text-slate-700">{{ $selectedPreviousAttendance->learning_journal }}</p>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No previous learning journal found for this student.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('attendances.store') }}" data-confirm="Save this attendance? Please double check the student, payment, and journal first.">
                    @csrf
                    <input type="hidden" name="mode" value="single">
                    <input type="hidden" name="student_id" value="{{ $resolvedStudentId }}">

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label value="Selected Student" />
                            <div class="mt-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                @if ($selectedStudentData)
                                    <p class="font-medium text-slate-900">{{ $selectedStudentData->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $selectedStudentData->phone }}</p>
                                    <p class="mt-3 text-xs text-slate-500">Need another student? Search and select from the panel on the left.</p>
                                @else
                                    <p class="text-sm text-amber-700">Please search and select a student first before saving attendance.</p>
                                @endif
                            </div>
                            <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Active Tokens" />
                            <div class="mt-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                @if ($resolvedStudentId && $activePayments->isNotEmpty())
                                    <p class="font-medium text-emerald-700">{{ $activeSessions }} sessions available</p>
                                    <p class="mt-1 text-sm text-slate-500">Attendance will automatically deduct from the oldest active payment first.</p>
                                    @if ($activePayments->count() > 1)
                                        <p class="mt-2 text-xs text-slate-500">Combined from {{ $activePayments->count() }} active payments.</p>
                                    @endif
                                @elseif ($resolvedStudentId)
                                    <p class="font-medium text-amber-700">No active token available</p>
                                    <p class="mt-1 text-sm text-slate-500">Attendance will be saved as token debt.</p>
                                @else
                                    <p class="text-sm text-slate-500">Select a student first to see the combined active token balance.</p>
                                @endif
                            </div>
                            <x-input-error :messages="$errors->get('payment_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date" value="Attendance Date" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="teaching_minutes_single" value="Total Mengajar (minutes)" />
                            <x-text-input id="teaching_minutes_single" name="teaching_minutes" type="number" min="1" max="600" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('mode', 'single') === 'single' ? old('teaching_minutes', 60) : 60" required />
                            <x-input-error :messages="$errors->get('teaching_minutes')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label value="Teachers for This Session" />
                            <p class="mt-1 text-sm text-slate-500">You can choose more than one teacher when this student is taught together.</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($teachers as $teacher)
                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <input
                                            type="checkbox"
                                            name="teacher_ids[]"
                                            value="{{ $teacher->id }}"
                                            class="mt-1 rounded border-slate-300 text-slate-900"
                                            @checked(in_array($teacher->id, $selectedTeacherIds, true))
                                        >
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">{{ $teacher->name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $teacher->email }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('teacher_ids')" class="mt-2" />
                            <x-input-error :messages="$errors->get('teacher_ids.*')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="learning_journal_single" value="Learning Journal" />
                            <textarea id="learning_journal_single" name="learning_journal" rows="5" class="mt-1 block w-full rounded-xl border-slate-300" required placeholder="Tuliskan materi yang diajarkan, progress murid, homework, atau catatan pembelajaran hari ini.">{{ old('mode', 'single') === 'single' ? old('learning_journal') : '' }}</textarea>
                            <x-input-error :messages="$errors->get('learning_journal')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="notes" value="Notes (optional)" />
                            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('mode', 'single') === 'single' ? old('notes') : '' }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    @if ($resolvedStudentId && $activePayments->isEmpty())
                        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            This student does not have an active payment. Attendance will be saved as token debt and deducted automatically from the next payment.
                        </div>
                    @endif

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('attendances.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                        <button
                            type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto"
                            @disabled(! $selectedStudentData)
                        >
                            Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="mode === 'group'" x-cloak class="rounded-3xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('attendances.store') }}" data-confirm="Save this group attendance? Please double check the class, students, and journal first.">
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

                    <div>
                        <x-input-label for="teaching_minutes_group" value="Total Mengajar (minutes)" />
                        <x-text-input id="teaching_minutes_group" name="teaching_minutes" type="number" min="1" max="600" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('mode') === 'group' ? old('teaching_minutes', 60) : 60" required />
                        <x-input-error :messages="$errors->get('teaching_minutes')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="group_learning_journal" value="Learning Journal" />
                        <textarea id="group_learning_journal" name="learning_journal" rows="5" class="mt-1 block w-full rounded-xl border-slate-300" required placeholder="Tuliskan materi kelas, progress grup, homework, atau catatan pembelajaran sesi ini.">{{ old('mode') === 'group' ? old('learning_journal') : '' }}</textarea>
                        <x-input-error :messages="$errors->get('learning_journal')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label value="Teachers for This Class" />
                        <p class="mt-1 text-sm text-slate-500">You can choose more than one teacher when this class is taught together.</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($teachers as $teacher)
                                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        name="group_teacher_ids[]"
                                        value="{{ $teacher->id }}"
                                        class="mt-1 rounded border-slate-300 text-slate-900"
                                        @checked(in_array($teacher->id, $selectedGroupTeacherIds, true))
                                    >
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900">{{ $teacher->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $teacher->email }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('group_teacher_ids')" class="mt-2" />
                        <x-input-error :messages="$errors->get('group_teacher_ids.*')" class="mt-2" />
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
                            <p class="text-sm text-slate-500">Active students without available sessions can still be marked present as token debt.</p>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('student_ids')" class="mt-2" />

                    <div class="mt-4">
                        <x-input-label for="group_student_search" value="Search Student" />
                        <x-text-input
                            id="group_student_search"
                            type="text"
                            x-model="groupStudentSearch"
                            class="mt-1 block w-full rounded-xl border-slate-300"
                            placeholder="Search by student name or phone"
                            autocomplete="off"
                        />
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($students as $student)
                            @php
                                $activePayments = $student->payments->where('remaining_sessions', '>', 0)->sortBy(['payment_date', 'id'])->values();
                                $activeSessions = (int) $activePayments->sum('remaining_sessions');
                                $previousAttendance = $student->latestAttendance;
                                $canAttend = true;
                                $studentSearchIndex = strtolower(implode(' ', array_filter([
                                    $student->name,
                                    $student->phone,
                                    $student->book_info,
                                ])));
                            @endphp
                            <label
                                x-show="matchesGroupStudent(@js($studentSearchIndex))"
                                class="flex items-start gap-3 rounded-2xl border p-4 {{ $activeSessions > 0 ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50' }}"
                            >
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
                                    @if ($student->book_info)
                                        <div class="mt-3 rounded-xl bg-sky-50 p-3 text-sm text-sky-900">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Book Info</p>
                                            <p class="mt-2 whitespace-pre-line">{{ $student->book_info }}</p>
                                        </div>
                                    @endif
                                    @if ($activeSessions > 0)
                                        <p class="mt-2 text-xs text-emerald-700">{{ $activeSessions }} sessions available</p>
                                        @if ($activePayments->count() > 1)
                                            <p class="mt-1 text-xs text-slate-500">Combined from {{ $activePayments->count() }} active payments.</p>
                                        @endif
                                    @else
                                        <p class="mt-2 text-xs text-amber-700">No active payment. Will be recorded as token debt.</p>
                                    @endif
                                    <div class="mt-3 rounded-xl bg-slate-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Previous Journal</p>
                                        @if ($previousAttendance)
                                            <p class="mt-2 text-xs text-slate-500">{{ $previousAttendance->date->format('d M Y') }} by {{ $previousAttendance->teacher?->name ?? '-' }}</p>
                                            <p class="mt-2 line-clamp-4 whitespace-pre-line text-sm text-slate-700">{{ $previousAttendance->learning_journal }}</p>
                                        @else
                                            <p class="mt-2 text-sm text-slate-500">No previous journal.</p>
                                        @endif
                                    </div>
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

    <script>
        function attendanceCreatePage({ mode, students, selectedStudentId, createUrl }) {
            return {
                mode,
                students,
                selectedStudentId,
                createUrl,
                studentSearch: '',
                groupStudentSearch: '',
                get filteredStudents() {
                    const query = this.studentSearch.trim().toLowerCase();

                    if (! query) {
                        return this.students.slice(0, 12);
                    }

                    return this.students
                        .filter((student) => {
                            const haystack = `${student.name} ${student.phone} ${student.book_info ?? ''}`.toLowerCase();

                            return haystack.includes(query);
                        })
                        .slice(0, 12);
                },
                openStudent(studentId) {
                    const url = new URL(this.createUrl, window.location.origin);
                    url.searchParams.set('student_id', studentId);
                    window.location.assign(url.toString());
                },
                matchesGroupStudent(haystack) {
                    if (! this.groupStudentSearch.trim()) {
                        return true;
                    }

                    return haystack.includes(this.groupStudentSearch.trim().toLowerCase());
                },
            };
        }
    </script>
</x-app-layout>
