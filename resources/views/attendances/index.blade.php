<x-app-layout>
    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (string) $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
        ])->values();
        $selectedStudentData = request('student_id') ? $students->firstWhere('id', request('student_id')) : null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Attendances</h2>
                <p class="text-sm text-slate-500">Every saved attendance consumes exactly one session from the active token balance.</p>
            </div>
            @if (auth()->user()->isTeacher())
                <a href="{{ route('attendances.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Attendance</a>
            @endif
        </div>
    </x-slot>

    <div
        class="mb-6 rounded-3xl bg-white p-5 shadow-sm"
        x-data="{
            studentSearch: '',
            selectedStudentId: @js((string) request('student_id', '')),
            students: @js($studentOptions),
            get filteredStudents() {
                const query = this.studentSearch.trim().toLowerCase();

                if (! query) {
                    return this.students.slice(0, 10);
                }

                return this.students
                    .filter((student) => `${student.name} ${student.phone}`.toLowerCase().includes(query))
                    .slice(0, 10);
            },
            selectStudent(studentId) {
                this.selectedStudentId = studentId;
            },
        }"
    >
        <form method="GET" action="{{ route('attendances.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <x-input-label for="date_from" value="Date From" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_from'] ?? null" />
            </div>

            <div>
                <x-input-label for="date_to" value="Date To" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_to'] ?? null" />
            </div>

            <div class="xl:col-span-2">
                <x-input-label for="student_search_filter" value="Student" />
                <input type="hidden" name="student_id" x-model="selectedStudentId">
                <x-text-input
                    id="student_search_filter"
                    type="text"
                    x-model="studentSearch"
                    class="mt-1 block w-full rounded-xl border-slate-300"
                    placeholder="Search by student name or phone"
                    autocomplete="off"
                />
                <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <button
                        type="button"
                        @click="selectStudent('')"
                        class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        <span class="font-medium text-slate-900">All students</span>
                        <span x-show="selectedStudentId === ''" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selected</span>
                    </button>
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
                            <span x-show="selectedStudentId === student.id" class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selected</span>
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
                    @else
                        <p class="text-sm text-slate-500">Filtering all students.</p>
                    @endif
                </div>
            </div>

            <div>
                <x-input-label for="teacher_id" value="Teacher" />
                <select id="teacher_id" name="teacher_id" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">All teachers</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-3 md:col-span-2 md:flex-row md:items-end xl:col-span-1">
                <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white md:w-auto">
                    Filter
                </button>
                <a href="{{ route('attendances.index') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 md:w-auto">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Rekap Mengajar Guru</h3>
                <p class="text-sm text-slate-500">Ringkasan sesi dan total mengajar berdasarkan filter periode di atas.</p>
            </div>
            @if (($filters['date_from'] ?? null) || ($filters['date_to'] ?? null))
                <p class="text-sm text-slate-500">
                    Period:
                    {{ $filters['date_from'] ?? '...' }}
                    -
                    {{ $filters['date_to'] ?? '...' }}
                </p>
            @endif
        </div>

        <div class="mt-4 space-y-3 md:hidden">
            @forelse ($teacherRecap as $recap)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">{{ $recap->teacher_name }}</p>
                    <div class="mt-3 grid gap-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Total sesi</span>
                            <span class="font-medium text-slate-900">{{ $recap->session_count }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Total mengajar</span>
                            <span class="font-medium text-slate-900">{{ $recap->teaching_duration_label }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                    Belum ada data mengajar pada periode ini.
                </div>
            @endforelse
        </div>

        <div class="mt-4 hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Guru</th>
                        <th class="px-4 py-3 font-medium">Total Sesi</th>
                        <th class="px-4 py-3 font-medium">Total Mengajar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($teacherRecap as $recap)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $recap->teacher_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $recap->session_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $recap->teaching_duration_label }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-slate-500">Belum ada data mengajar pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($attendances as $attendance)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $attendance->student_label }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $attendance->date->format('d M Y') }}</p>
                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $attendance->session_label }}</p>
                        @if ($attendance->type === 'batch')
                            <p class="mt-1 text-xs text-slate-500">{{ $attendance->student_count }} students present</p>
                        @endif
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $attendance->payment_is_debt ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $attendance->payment_label }}
                    </span>
                </div>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Teacher</dt>
                        <dd class="font-medium text-slate-900">{{ $attendance->teacher_name }}</dd>
                    </div>
                    @if ($attendance->type === 'batch')
                        <div>
                            <dt class="text-slate-500">Students Present</dt>
                            <dd class="mt-1 text-slate-900">{{ $attendance->student_label }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-slate-500">Learning Journal</dt>
                        <dd class="mt-1 whitespace-pre-line text-slate-900">{{ $attendance->learning_journal }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Notes</dt>
                        <dd class="mt-1 text-slate-900">{{ $attendance->notes }}</dd>
                    </div>
                </dl>
                @if (auth()->user()->isTeacher())
                    <div class="mt-4">
                    @if ($attendance->editable)
                        <a href="{{ route('attendances.edit', $attendance->attendance) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                            Edit
                        </a>
                    @else
                        <span class="text-xs text-slate-500">Group attendance is not editable per student.</span>
                    @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No attendances recorded.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Date</th>
                    <th class="px-6 py-3 font-medium">Session</th>
                    <th class="px-6 py-3 font-medium">Student</th>
                    <th class="px-6 py-3 font-medium">Teacher</th>
                    <th class="px-6 py-3 font-medium">Package</th>
                    <th class="px-6 py-3 font-medium">Learning Journal</th>
                    <th class="px-6 py-3 font-medium">Notes</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($attendances as $attendance)
                    <tr>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->session_label }}</td>
                        <td class="px-6 py-4 font-medium text-slate-900">
                            <p>{{ $attendance->student_label }}</p>
                            @if ($attendance->type === 'batch')
                                <p class="mt-1 text-xs font-normal text-slate-500">{{ $attendance->student_count }} students</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->teacher_name }}</td>
                        <td class="px-6 py-4">
                            <span class="{{ $attendance->payment_is_debt ? 'font-medium text-amber-700' : 'text-slate-600' }}">
                                {{ $attendance->payment_label }}
                            </span>
                        </td>
                        <td class="max-w-xs px-6 py-4 text-slate-600">
                            <p class="line-clamp-3 whitespace-pre-line">{{ $attendance->learning_journal }}</p>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->notes }}</td>
                        <td class="px-6 py-4 text-right">
                            @if (auth()->user()->isTeacher() && $attendance->editable)
                                <a href="{{ route('attendances.edit', $attendance->attendance) }}" class="text-sm font-medium text-slate-700">Edit</a>
                            @elseif (auth()->user()->isTeacher())
                                <span class="text-xs text-slate-500">Batch</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-slate-500">No attendances recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $attendances->links() }}
    </div>
</x-app-layout>
