<x-app-layout>
    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (string) $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
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
        $selectedTeacherIds = collect(old('teacher_ids', $attendance->teachers->pluck('id')->whenEmpty(fn ($collection) => $collection->push($attendance->teacher_id))->all()))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $selectedMaterialLinkIds = collect(old('material_link_ids', $attendance->materialLinks->pluck('id')->whenEmpty(fn ($collection) => $attendance->material_link_id ? $collection->push($attendance->material_link_id) : $collection)->all()))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
        $resolvedStudentId = old('student_id', $selectedStudent ?? $attendance->student_id);
        $selectedStudentData = $resolvedStudentId ? $students->firstWhere('id', $resolvedStudentId) : null;
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Attendance</h2>
    </x-slot>

    <div
        x-data="attendanceEditPage({
            students: @js($studentOptions),
            selectedStudentId: @js((string) ($resolvedStudentId ?: '')),
            selectedMaterialLinkIds: @js($selectedMaterialLinkIds),
            editUrl: @js(route('attendances.edit', $attendance)),
        })"
        class="rounded-3xl bg-white p-6 shadow-sm"
    >
        <form method="POST" action="{{ route('attendances.update', $attendance) }}" data-confirm="Update this attendance? Please double check the student, payment, and journal first.">
            @csrf
            @method('PUT')
            <input type="hidden" name="student_id" value="{{ $resolvedStudentId }}">

            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="student_search_edit" value="Search Student" />
                    <x-text-input
                        id="student_search_edit"
                        type="text"
                        x-model="studentSearch"
                        class="mt-1 block w-full rounded-xl border-slate-300"
                        placeholder="Search by student name or phone"
                        autocomplete="off"
                    />
                    <div class="mt-3 max-h-72 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3">
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
                            No student found. Try another keyword.
                        </p>
                    </div>

                    <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        @if ($selectedStudentData)
                            <p class="font-medium text-slate-900">{{ $selectedStudentData->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedStudentData->phone }}</p>
                            <p class="mt-3 text-xs text-slate-500">Need another student? Search and select above.</p>
                        @else
                            <p class="text-sm text-amber-700">Please search and select a student first.</p>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label value="Active Tokens" />
                    <div class="mt-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        @if ($resolvedStudentId == $attendance->student_id && $attendance->payment)
                            <p class="font-medium text-emerald-700">Original payment will be kept</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $attendance->payment->displayLabel() }} remains attached to this attendance unless you change the student.</p>
                        @elseif ($activeSessions > 0)
                            <p class="font-medium text-emerald-700">{{ $activeSessions }} sessions available</p>
                            <p class="mt-1 text-sm text-slate-500">If the student changes, attendance will use the oldest active payment automatically.</p>
                            @if ($activePayments->count() > 1)
                                <p class="mt-2 text-xs text-slate-500">Combined from {{ $activePayments->count() }} active payments.</p>
                            @endif
                        @else
                            <p class="font-medium text-amber-700">No active token available</p>
                            <p class="mt-1 text-sm text-slate-500">If you switch to this student, attendance will become token debt.</p>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('payment_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date" value="Attendance Date" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', $attendance->date->toDateString())" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="teaching_minutes" value="Total Mengajar (minutes)" />
                    <x-text-input id="teaching_minutes" name="teaching_minutes" type="number" min="1" max="600" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('teaching_minutes', $attendance->teaching_minutes ?: 60)" required />
                    <x-input-error :messages="$errors->get('teaching_minutes')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label value="Link Materi (optional)" />
                    <p class="mt-1 text-sm text-slate-500">Boleh pilih lebih dari satu link materi untuk attendance ini.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach ($materialLinks as $materialLink)
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <input
                                    type="checkbox"
                                    name="material_link_ids[]"
                                    value="{{ $materialLink->id }}"
                                    x-model="selectedMaterialLinkIds"
                                    class="mt-1 rounded border-slate-300 text-slate-900"
                                    @checked(in_array((string) $materialLink->id, $selectedMaterialLinkIds, true))
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
                    <x-input-error :messages="$errors->get('material_link_ids')" class="mt-2" />
                    <x-input-error :messages="$errors->get('material_link_ids.*')" class="mt-2" />
                </div>

                <div x-show="selectedStudentMaterialWarnings.length > 0" x-cloak class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Warning Materi</p>
                    <div class="mt-3 space-y-2">
                        <template x-for="warning in selectedStudentMaterialWarnings" :key="`edit-single-material-warning-${warning.material_link_id}`">
                            <div class="rounded-xl bg-white px-3 py-3 text-sm text-slate-700">
                                <p class="font-medium text-slate-900" x-text="warning.title"></p>
                                <p class="mt-1">
                                    Murid ini sudah pernah belajar materi ini pada
                                    <span class="font-medium" x-text="warning.last_date"></span>
                                    bersama
                                    <span class="font-medium" x-text="warning.teacher_name || '-'"></span>.
                                </p>
                                <p class="mt-1 text-xs text-amber-700">
                                    Total pernah dipakai:
                                    <span x-text="warning.count"></span> kali.
                                </p>
                            </div>
                        </template>
                    </div>
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
                    <x-input-label for="learning_journal" value="Learning Journal" />
                    <textarea id="learning_journal" name="learning_journal" rows="5" class="mt-1 block w-full rounded-xl border-slate-300" required>{{ old('learning_journal', $attendance->learning_journal) }}</textarea>
                    <x-input-error :messages="$errors->get('learning_journal')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="homework_content" value="Homework / Target Hafalan (optional)" />
                    <textarea id="homework_content" name="homework_content" rows="7" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Tuliskan phrases, chunks, atau target hafalan untuk diuji di pertemuan berikutnya.">{{ old('homework_content', $attendance->homework_content) }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Field ini mendukung isi yang cukup panjang untuk menyimpan homework per murid.</p>
                    <x-input-error :messages="$errors->get('homework_content')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="notes" value="Notes (optional)" />
                    <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes', $attendance->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('attendances.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
                <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
                    Update Attendance
                </x-primary-button>
            </div>
        </form>
    </div>

    <script>
        function attendanceEditPage({ students, selectedStudentId, selectedMaterialLinkIds, editUrl }) {
            return {
                students,
                selectedStudentId,
                selectedMaterialLinkIds,
                editUrl,
                studentSearch: '',
                get selectedStudent() {
                    return this.students.find((student) => student.id === this.selectedStudentId) ?? null;
                },
                materialWarningFor(student, materialLinkId) {
                    if (! student || ! materialLinkId) {
                        return null;
                    }

                    return (student.material_history ?? []).find((item) => item.material_link_id === materialLinkId) ?? null;
                },
                get selectedStudentMaterialWarnings() {
                    return this.selectedMaterialLinkIds
                        .map((materialLinkId) => this.materialWarningFor(this.selectedStudent, materialLinkId))
                        .filter(Boolean);
                },
                get filteredStudents() {
                    const query = this.studentSearch.trim().toLowerCase();

                    if (! query) {
                        return this.students.slice(0, 12);
                    }

                    return this.students
                        .filter((student) => `${student.name} ${student.phone}`.toLowerCase().includes(query))
                        .slice(0, 12);
                },
                openStudent(studentId) {
                    const url = new URL(this.editUrl, window.location.origin);
                    url.searchParams.set('student_id', studentId);
                    window.location.assign(url.toString());
                },
            };
        }
    </script>
</x-app-layout>
