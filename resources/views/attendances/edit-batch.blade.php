<x-app-layout>
    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (string) $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
            'book_info' => $student->book_info,
            'material_history' => $student->attendances
                ->flatMap(function ($attendanceItem) {
                    $materialLinks = $attendanceItem->materialLinks->isNotEmpty()
                        ? $attendanceItem->materialLinks
                        : ($attendanceItem->materialLink ? collect([$attendanceItem->materialLink]) : collect());

                    return $materialLinks->map(fn ($materialLink) => [
                        'material_link_id' => (string) $materialLink->id,
                        'title' => $materialLink->title,
                        'last_date' => $attendanceItem->date?->format('d M Y'),
                        'teacher_name' => $attendanceItem->teacher?->name,
                    ]);
                })
                ->groupBy('material_link_id')
                ->map(fn ($histories, $materialLinkId) => [
                    'material_link_id' => (string) $materialLinkId,
                    'title' => $histories->first()['title'] ?? null,
                    'last_date' => $histories->first()['last_date'] ?? null,
                    'teacher_name' => $histories->first()['teacher_name'] ?? null,
                    'count' => $histories->count(),
                ])
                ->values()
                ->all(),
        ])->values();
        $existingAttendances = $attendanceBatch->attendances->keyBy('student_id');
        $isPerStudentJournal = $attendanceBatch->learning_journal === 'Individual learning journals recorded for each student.';
        $existingHomeworkValues = $attendanceBatch->attendances
            ->map(fn ($attendance) => trim((string) $attendance->homework_content))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values();
        $isPerStudentHomework = $existingHomeworkValues->count() > 1;
        $selectedGroupTeacherIds = collect(old('group_teacher_ids', $attendanceBatch->teachers->pluck('id')->whenEmpty(fn ($collection) => $collection->push($attendanceBatch->teacher_id))->all()))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $selectedGroupStudentIds = collect(old('student_ids', $attendanceBatch->attendances->pluck('student_id')->map(fn ($id) => (string) $id)->all()))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
        $selectedGroupMaterialLinkIds = collect(old('group_material_link_ids', $attendanceBatch->materialLinks->pluck('id')->whenEmpty(fn ($collection) => $attendanceBatch->material_link_id ? $collection->push($attendanceBatch->material_link_id) : $collection)->all()))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
        $groupJournalMode = old('group_journal_mode', $isPerStudentJournal ? 'per_student' : 'group');
        $groupHomeworkMode = old('group_homework_mode', $isPerStudentHomework ? 'per_student' : 'group');
        $defaultGroupHomeworkContent = $isPerStudentHomework ? '' : $existingHomeworkValues->first();
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Batch Attendance</h2>
    </x-slot>

    <div
        x-data="attendanceBatchEditPage({
            students: @js($studentOptions),
            selectedGroupStudentIds: @js($selectedGroupStudentIds),
            groupJournalMode: @js($groupJournalMode),
            groupHomeworkMode: @js($groupHomeworkMode),
            selectedGroupMaterialLinkIds: @js($selectedGroupMaterialLinkIds),
        })"
        class="rounded-3xl bg-white p-6 shadow-sm"
    >
        <form method="POST" action="{{ route('attendances.batches.update', $attendanceBatch) }}" data-confirm="Update this batch attendance? Please double check the teachers, selected students, and journals first.">
            @csrf
            @method('PUT')
            <input type="hidden" name="mode" value="group">

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="group_title" value="Class / Session Name" />
                    <x-text-input id="group_title" name="group_title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('group_title', $attendanceBatch->title)" required />
                    <x-input-error :messages="$errors->get('group_title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="group_date" value="Attendance Date" />
                    <x-text-input id="group_date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', $attendanceBatch->date->toDateString())" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="teaching_minutes_group" value="Total Mengajar (minutes)" />
                    <x-text-input id="teaching_minutes_group" name="teaching_minutes" type="number" min="1" max="600" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('teaching_minutes', $attendanceBatch->teaching_minutes ?: 60)" required />
                    <x-input-error :messages="$errors->get('teaching_minutes')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label value="Link Materi (optional)" />
                    <p class="mt-1 text-sm text-slate-500">Boleh pilih lebih dari satu link materi untuk batch attendance ini.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach ($materialLinks as $materialLink)
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <input
                                    type="checkbox"
                                    name="group_material_link_ids[]"
                                    value="{{ $materialLink->id }}"
                                    x-model="selectedGroupMaterialLinkIds"
                                    class="mt-1 rounded border-slate-300 text-slate-900"
                                    @checked(in_array((string) $materialLink->id, $selectedGroupMaterialLinkIds, true))
                                >
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900">{{ $materialLink->title }}</p>
                                    <a href="{{ $materialLink->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex break-all text-xs text-sky-700 hover:text-sky-900">
                                        {{ $materialLink->url }}
                                    </a>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('group_material_link_ids')" class="mt-2" />
                    <x-input-error :messages="$errors->get('group_material_link_ids.*')" class="mt-2" />
                </div>

                <div x-show="groupMaterialWarnings.length > 0" x-cloak class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">Warning Materi untuk Grup</p>
                    <p class="mt-1 text-xs text-amber-700">Beberapa murid yang dipilih sudah pernah belajar salah satu materi yang dipilih.</p>
                    <div class="mt-3 space-y-2">
                        <template x-for="warning in groupMaterialWarnings" :key="`edit-material-warning-${warning.id}-${warning.warning.material_link_id}`">
                            <div class="rounded-xl bg-white px-3 py-3 text-sm text-slate-700">
                                <p class="font-medium text-slate-900" x-text="warning.name"></p>
                                <p class="mt-1">
                                    Sudah pernah belajar
                                    <span class="font-medium" x-text="warning.warning.title"></span>
                                    pada
                                    <span class="font-medium" x-text="warning.warning.last_date"></span>
                                    bersama
                                    <span class="font-medium" x-text="warning.warning.teacher_name || '-'"></span>.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Total pernah dipakai:
                                    <span x-text="warning.warning.count"></span> kali.
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <x-input-label value="Teaching Journal Mode" />
                    <p class="mt-1 text-sm text-slate-500">Pilih apakah jurnal pembelajaran akan sama untuk seluruh grup, atau diisi khusus per murid.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="radio" name="group_journal_mode" value="group" x-model="groupJournalMode" class="mt-1 border-slate-300 text-slate-900">
                            <div>
                                <p class="font-medium text-slate-900">Satu Jurnal untuk Grup</p>
                                <p class="text-sm text-slate-500">Gunakan kalau semua murid belajar materi yang sama.</p>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="radio" name="group_journal_mode" value="per_student" x-model="groupJournalMode" class="mt-1 border-slate-300 text-slate-900">
                            <div>
                                <p class="font-medium text-slate-900">Jurnal per Murid</p>
                                <p class="text-sm text-slate-500">Gunakan kalau tiap murid punya materi yang berbeda.</p>
                            </div>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('group_journal_mode')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label value="Homework Mode" />
                    <p class="mt-1 text-sm text-slate-500">Pilih apakah homework sama untuk seluruh grup, atau diisi khusus per murid.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="radio" name="group_homework_mode" value="group" x-model="groupHomeworkMode" class="mt-1 border-slate-300 text-slate-900">
                            <div>
                                <p class="font-medium text-slate-900">Satu Homework untuk Grup</p>
                                <p class="text-sm text-slate-500">Gunakan kalau seluruh murid mendapat target hafalan atau phrases yang sama.</p>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="radio" name="group_homework_mode" value="per_student" x-model="groupHomeworkMode" class="mt-1 border-slate-300 text-slate-900">
                            <div>
                                <p class="font-medium text-slate-900">Homework per Murid</p>
                                <p class="text-sm text-slate-500">Gunakan kalau setiap murid punya target hafalan yang berbeda.</p>
                            </div>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('group_homework_mode')" class="mt-2" />
                </div>

                <div class="md:col-span-2" x-show="groupJournalMode === 'group'" x-cloak>
                    <x-input-label for="group_learning_journal" value="Learning Journal" />
                    <textarea id="group_learning_journal" name="learning_journal" rows="5" class="mt-1 block w-full rounded-xl border-slate-300" :required="groupJournalMode === 'group'">{{ old('learning_journal', $isPerStudentJournal ? '' : $attendanceBatch->learning_journal) }}</textarea>
                    <x-input-error :messages="$errors->get('learning_journal')" class="mt-2" />
                </div>

                <div class="md:col-span-2" x-show="groupHomeworkMode === 'group'" x-cloak>
                    <x-input-label for="group_homework_content" value="Homework / Target Hafalan Grup (optional)" />
                    <textarea id="group_homework_content" name="group_homework_content" rows="7" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Tuliskan target hafalan, phrases, atau chunks untuk seluruh grup.">{{ old('group_homework_content', $defaultGroupHomeworkContent) }}</textarea>
                    <x-input-error :messages="$errors->get('group_homework_content')" class="mt-2" />
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
                    <textarea id="group_notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes', $attendanceBatch->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Students Present</h3>
                        <p class="text-sm text-slate-500">Tambah atau hapus murid dari batch ini. Token akan disesuaikan otomatis saat disimpan.</p>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('student_ids')" class="mt-2" />

                <div x-show="selectedGroupStudents.length > 0" x-cloak class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-emerald-900">Selected Students</p>
                            <p class="mt-1 text-xs text-emerald-700">Double check the list before updating this batch.</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700" x-text="`${selectedGroupStudents.length} selected`"></span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <template x-for="student in selectedGroupStudents" :key="`selected-${student.id}`">
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm font-medium text-slate-700 shadow-sm" x-text="student.name"></span>
                        </template>
                    </div>
                </div>

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
                            $batchAttendance = $existingAttendances->get($student->id);
                            $activePayments = $student->payments->where('remaining_sessions', '>', 0)->sortBy(['payment_date', 'id'])->values();
                            $currentSessions = (int) $activePayments->sum('remaining_sessions') + ($batchAttendance && $batchAttendance->payment_id ? 1 : 0);
                            $previousAttendance = $student->latestAttendance;
                            $studentSearchIndex = strtolower(implode(' ', array_filter([
                                $student->name,
                                $student->phone,
                                $student->book_info,
                            ])));
                        @endphp
                        <label
                            x-show="matchesGroupStudent(@js($studentSearchIndex))"
                            class="flex items-start gap-3 rounded-2xl border p-4 {{ $currentSessions > 0 ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50' }}"
                        >
                            <input
                                type="checkbox"
                                name="student_ids[]"
                                value="{{ $student->id }}"
                                x-model="selectedGroupStudentIds"
                                class="mt-1 rounded border-slate-300 text-slate-900"
                                @checked(in_array((string) $student->id, $selectedGroupStudentIds, true))
                            >
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">{{ $student->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $student->phone }}</p>
                                @if ($batchAttendance)
                                    <p class="mt-2 text-xs font-semibold text-sky-700">Already included in this batch</p>
                                @endif
                                @if ($student->book_info)
                                    <div class="mt-3 rounded-xl bg-sky-50 p-3 text-sm text-sky-900">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Book Info</p>
                                        <p class="mt-2 whitespace-pre-line">{{ $student->book_info }}</p>
                                    </div>
                                @endif
                                @if ($currentSessions > 0)
                                    <p class="mt-2 text-xs text-emerald-700">{{ $currentSessions }} sessions available / reserved</p>
                                @else
                                    <p class="mt-2 text-xs text-amber-700">No active payment. If added, it will be recorded as token debt.</p>
                                @endif
                                <div class="mt-3 rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current / Previous Journal</p>
                                    @if ($batchAttendance)
                                        <p class="mt-2 text-xs text-slate-500">Current batch entry</p>
                                        <p class="mt-2 line-clamp-4 whitespace-pre-line text-sm text-slate-700">{{ $batchAttendance->learning_journal }}</p>
                                    @elseif ($previousAttendance)
                                        <p class="mt-2 text-xs text-slate-500">{{ $previousAttendance->date->format('d M Y') }} by {{ $previousAttendance->teacher?->name ?? '-' }}</p>
                                        <p class="mt-2 line-clamp-4 whitespace-pre-line text-sm text-slate-700">{{ $previousAttendance->learning_journal }}</p>
                                    @else
                                        <p class="mt-2 text-sm text-slate-500">No previous journal.</p>
                                    @endif
                                </div>
                                <div class="mt-3 rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current / Previous Homework</p>
                                    @if ($batchAttendance?->homework_content)
                                        <p class="mt-2 text-xs text-slate-500">Current batch entry</p>
                                        <p class="mt-2 line-clamp-6 whitespace-pre-line text-sm text-slate-700">{{ $batchAttendance->homework_content }}</p>
                                    @elseif ($student->latestHomeworkAttendance?->homework_content)
                                        <p class="mt-2 text-xs text-slate-500">{{ $student->latestHomeworkAttendance->date->format('d M Y') }} by {{ $student->latestHomeworkAttendance->teacher?->name ?? '-' }}</p>
                                        <p class="mt-2 line-clamp-6 whitespace-pre-line text-sm text-slate-700">{{ $student->latestHomeworkAttendance->homework_content }}</p>
                                    @else
                                        <p class="mt-2 text-sm text-slate-500">No previous homework.</p>
                                    @endif
                                </div>
                                <div x-show="groupJournalMode === 'per_student'" x-cloak class="mt-3">
                                    <x-input-label for="student_learning_journal_{{ $student->id }}" value="Learning Journal for {{ $student->name }}" />
                                    <textarea
                                        id="student_learning_journal_{{ $student->id }}"
                                        name="student_learning_journals[{{ $student->id }}]"
                                        rows="4"
                                        class="mt-1 block w-full rounded-xl border-slate-300"
                                        placeholder="Tuliskan materi atau progress khusus untuk {{ $student->name }}."
                                    >{{ old('student_learning_journals.'.$student->id, $batchAttendance?->learning_journal) }}</textarea>
                                    <x-input-error :messages="$errors->get('student_learning_journals.'.$student->id)" class="mt-2" />
                                </div>
                                <div x-show="groupHomeworkMode === 'per_student'" x-cloak class="mt-3">
                                    <x-input-label for="student_homework_content_{{ $student->id }}" value="Homework for {{ $student->name }} (optional)" />
                                    <textarea
                                        id="student_homework_content_{{ $student->id }}"
                                        name="student_homework_contents[{{ $student->id }}]"
                                        rows="6"
                                        class="mt-1 block w-full rounded-xl border-slate-300"
                                        placeholder="Tuliskan target hafalan atau phrases khusus untuk {{ $student->name }}."
                                    >{{ old('student_homework_contents.'.$student->id, $batchAttendance?->homework_content) }}</textarea>
                                    <x-input-error :messages="$errors->get('student_homework_contents.'.$student->id)" class="mt-2" />
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('attendances.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
                    Update Batch Attendance
                </x-primary-button>
            </div>
        </form>
    </div>

    <script>
        function attendanceBatchEditPage({ students, selectedGroupStudentIds, groupJournalMode, groupHomeworkMode, selectedGroupMaterialLinkIds }) {
            return {
                students,
                selectedGroupStudentIds,
                groupJournalMode,
                groupHomeworkMode,
                selectedGroupMaterialLinkIds,
                groupStudentSearch: '',
                get selectedGroupStudents() {
                    return this.students.filter((student) => this.selectedGroupStudentIds.includes(student.id));
                },
                materialWarningFor(student, materialLinkId) {
                    if (! student || ! materialLinkId) {
                        return null;
                    }

                    return (student.material_history ?? []).find((item) => item.material_link_id === materialLinkId) ?? null;
                },
                get groupMaterialWarnings() {
                    if (this.selectedGroupMaterialLinkIds.length === 0) {
                        return [];
                    }

                    return this.selectedGroupStudents.flatMap((student) =>
                        this.selectedGroupMaterialLinkIds
                            .map((materialLinkId) => {
                                const warning = this.materialWarningFor(student, materialLinkId);

                                if (! warning) {
                                    return null;
                                }

                                return {
                                    ...student,
                                    warning,
                                };
                            })
                            .filter(Boolean)
                    );
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
