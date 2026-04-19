<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'student_id' => ['nullable', 'exists:students,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            $filters['date_from'] = now()->toDateString();
            $filters['date_to'] = now()->toDateString();
        }

        $attendanceRows = Attendance::with(['teachers', 'batch.teachers', 'student', 'teacher', 'payment.package'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($filters['teacher_id'] ?? null, function ($query, $teacherId) {
                $query->where(function ($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId)
                        ->orWhereHas('teachers', fn ($attendanceQuery) => $attendanceQuery->whereKey($teacherId))
                        ->orWhereHas('batch.teachers', fn ($batchQuery) => $batchQuery->whereKey($teacherId));
                });
            })
            ->latest('date')
            ->latest('attendance_batch_id')
            ->latest('id')
            ->get();

        $attendanceEntries = $attendanceRows
            ->groupBy(fn (Attendance $attendance) => $attendance->attendance_batch_id
                ? 'batch-'.$attendance->attendance_batch_id
                : 'single-'.$attendance->id)
            ->map(function ($group) {
                /** @var Attendance $first */
                $first = $group->first();

                if ($first->attendance_batch_id) {
                    $studentNames = $group->pluck('student.name')->sort()->values();
                    $teacherNames = collect([$first->teacher->name])
                        ->merge($first->batch?->teachers?->pluck('name') ?? collect())
                        ->filter()
                        ->unique()
                        ->values();
                    $paymentLabels = $group->map(fn (Attendance $attendance) => $attendance->payment?->displayLabel() ?? 'Token Debt')
                        ->unique()
                        ->values();

                    return (object) [
                        'type' => 'batch',
                        'date' => $first->date,
                        'session_label' => $first->batch?->title ?: 'Group Class',
                        'teaching_minutes' => $this->resolveTeachingMinutes($first->batch?->teaching_minutes ?? $first->teaching_minutes),
                        'teaching_duration_label' => $this->formatTeachingMinutes($this->resolveTeachingMinutes($first->batch?->teaching_minutes ?? $first->teaching_minutes)),
                        'student_label' => $studentNames->join(', '),
                        'student_names' => $studentNames,
                        'student_count' => $studentNames->count(),
                        'teacher_name' => $teacherNames->join(', '),
                        'payment_label' => $paymentLabels->count() === 1 ? $paymentLabels->first() : 'Mixed Payments',
                        'payment_is_debt' => $paymentLabels->count() === 1 && $paymentLabels->first() === 'Token Debt',
                        'learning_journal' => $first->learning_journal,
                        'notes' => $first->notes ?: '-',
                        'editable' => false,
                        'attendance' => null,
                    ];
                }

                $teacherNames = collect([$first->teacher->name])
                    ->merge($first->teachers->pluck('name'))
                    ->filter()
                    ->unique()
                    ->values();

                return (object) [
                    'type' => 'single',
                    'date' => $first->date,
                    'session_label' => 'Single Attendance',
                    'teaching_minutes' => $this->resolveTeachingMinutes($first->teaching_minutes),
                    'teaching_duration_label' => $this->formatTeachingMinutes($this->resolveTeachingMinutes($first->teaching_minutes)),
                    'student_label' => $first->student->name,
                    'student_names' => collect([$first->student->name]),
                    'student_count' => 1,
                    'teacher_name' => $teacherNames->join(', '),
                    'payment_label' => $first->payment?->displayLabel() ?? 'Token Debt',
                    'payment_is_debt' => $first->payment === null,
                    'learning_journal' => $first->learning_journal,
                    'notes' => $first->notes ?: '-',
                    'editable' => true,
                    'attendance' => $first,
                ];
            })
            ->values();

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginatedEntries = new LengthAwarePaginator(
            $attendanceEntries->forPage($currentPage, $perPage)->values(),
            $attendanceEntries->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        $students = Student::orderBy('name')->get();
        $teachers = User::teachers()->orderBy('name')->get();
        $teacherRecap = $this->teacherRecap($filters);

        return view('attendances.index', [
            'attendances' => $paginatedEntries,
            'students' => $students,
            'teachers' => $teachers,
            'filters' => $filters,
            'teacherRecap' => $teacherRecap,
        ]);
    }

    public function create(Request $request)
    {
        $students = $this->attendanceFormData();
        $teachers = User::teachers()->orderBy('name')->get();
        $selectedStudent = $request->integer('student_id');
        $selectedPreviousAttendance = null;
        $activePayments = collect();
        $activeSessions = 0;

        if ($selectedStudent) {
            $selectedPreviousAttendance = $students
                ->firstWhere('id', $selectedStudent)
                ?->latestAttendance;

            $activePayments = $this->activePaymentsForStudent($selectedStudent);
            $activeSessions = (int) $activePayments->sum('remaining_sessions');
        }

        return view('attendances.create', compact('students', 'teachers', 'selectedStudent', 'selectedPreviousAttendance', 'activePayments', 'activeSessions'));
    }

    public function edit(Request $request, Attendance $attendance)
    {
        abort_if($attendance->attendance_batch_id, 403, 'Group attendance entries cannot be edited individually.');

        $students = $this->attendanceFormData();
        $selectedStudent = $request->integer('student_id') ?: $attendance->student_id;
        $activePayments = $this->activePaymentsForStudent($selectedStudent, $attendance->payment_id);
        $activeSessions = (int) $activePayments->sum('remaining_sessions');

        $teachers = User::teachers()->orderBy('name')->get();

        return view('attendances.edit', compact('attendance', 'students', 'teachers', 'activePayments', 'activeSessions', 'selectedStudent'));
    }

    public function store(AttendanceRequest $request)
    {
        if ($request->input('mode') === 'group') {
            return $this->storeGroupAttendance($request);
        }

        DB::transaction(function () use ($request): void {
            $payment = $this->resolvePaymentForAttendance(
                studentId: $request->integer('student_id'),
                requestedPaymentId: $request->integer('payment_id') ?: null,
            );

            $attendancePayload = [
                'student_id' => $request->integer('student_id'),
                'teacher_id' => $request->user()->id,
                'payment_id' => $payment?->id,
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $request->string('learning_journal')->toString(),
            ];

            if ($this->attendanceTableHasTeachingMinutes()) {
                $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $attendance = Attendance::create($attendancePayload);

            $attendance->teachers()->sync($this->resolveTeacherIds(
                requestedTeacherIds: $request->input('teacher_ids', []),
                fallbackTeacherId: $request->user()->id,
            ));

            $payment?->decrement('remaining_sessions');
        });

        return redirect()->route('attendances.index')->with('status', 'Attendance saved and session deducted.');
    }

    public function update(AttendanceUpdateRequest $request, Attendance $attendance)
    {
        abort_if($attendance->attendance_batch_id, 403, 'Group attendance entries cannot be edited individually.');

        DB::transaction(function () use ($request, $attendance): void {
            $oldPayment = $attendance->payment_id
                ? Payment::query()->whereKey($attendance->payment_id)->lockForUpdate()->firstOrFail()
                : null;
            $sameStudent = $attendance->student_id === $request->integer('student_id');
            $newPayment = $sameStudent
                ? $oldPayment
                : $this->resolvePaymentForAttendance($request->integer('student_id'));

            if (($oldPayment?->id) !== ($newPayment?->id)) {
                $oldPayment?->increment('remaining_sessions');
                $newPayment?->decrement('remaining_sessions');
            }

            $attendancePayload = [
                'student_id' => $request->integer('student_id'),
                'teacher_id' => $request->user()->id,
                'payment_id' => $newPayment?->id,
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $request->string('learning_journal')->toString(),
            ];

            if ($this->attendanceTableHasTeachingMinutes()) {
                $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $attendance->update($attendancePayload);

            $attendance->teachers()->sync($this->resolveTeacherIds(
                requestedTeacherIds: $request->input('teacher_ids', []),
                fallbackTeacherId: $request->user()->id,
            ));
        });

        return redirect()->route('attendances.index')->with('status', 'Attendance updated successfully.');
    }

    protected function storeGroupAttendance(AttendanceRequest $request)
    {
        $studentIds = collect($request->input('student_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($request, $studentIds): void {
            $batchPayload = [
                'title' => $request->string('group_title')->toString(),
                'teacher_id' => $request->user()->id,
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $request->string('learning_journal')->toString(),
            ];

            if ($this->attendanceBatchTableHasTeachingMinutes()) {
                $batchPayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $batch = AttendanceBatch::create($batchPayload);

            $teacherIds = $this->resolveTeacherIds(
                requestedTeacherIds: $request->input('group_teacher_ids', []),
                fallbackTeacherId: $request->user()->id,
            );

            $batch->teachers()->sync($teacherIds);

            foreach ($studentIds as $studentId) {
                $payment = $this->resolvePaymentForAttendance($studentId);

                $attendancePayload = [
                    'attendance_batch_id' => $batch->id,
                    'student_id' => $studentId,
                    'teacher_id' => $request->user()->id,
                    'payment_id' => $payment?->id,
                    'date' => $request->date('date'),
                    'notes' => $request->string('notes')->toString(),
                    'learning_journal' => $request->string('learning_journal')->toString(),
                ];

                if ($this->attendanceTableHasTeachingMinutes()) {
                    $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
                }

                Attendance::create($attendancePayload);

                $payment?->decrement('remaining_sessions');
            }
        });

        return redirect()->route('attendances.index')->with('status', 'Group attendance saved and sessions deducted for selected students.');
    }

    protected function attendanceFormData()
    {
        return Student::active()
            ->with(['payments' => fn ($query) => $query->active(), 'latestAttendance.teacher', 'latestAttendance.batch'])
            ->orderBy('name')
            ->get();
    }

    protected function activePaymentsForStudent(int $studentId, ?int $includePaymentId = null)
    {
        return Payment::query()
            ->with('package')
            ->where('student_id', $studentId)
            ->when($includePaymentId, function ($query, $includePaymentId) {
                $query->where(function ($query) use ($includePaymentId) {
                    $query->where('remaining_sessions', '>', 0)
                        ->orWhere('id', $includePaymentId);
                });
            }, fn ($query) => $query->where('remaining_sessions', '>', 0))
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
    }

    protected function resolvePaymentForAttendance(int $studentId, ?int $requestedPaymentId = null): ?Payment
    {
        if ($requestedPaymentId) {
            $payment = Payment::query()
                ->whereKey($requestedPaymentId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->remaining_sessions <= 0) {
                throw ValidationException::withMessages([
                    'payment_id' => 'The selected payment has no remaining sessions.',
                ]);
            }

            return $payment;
        }

        return Payment::query()
            ->where('student_id', $studentId)
            ->where('remaining_sessions', '>', 0)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    protected function resolveTeacherIds(array $requestedTeacherIds, int $fallbackTeacherId)
    {
        return collect($requestedTeacherIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->push($fallbackTeacherId)
            ->unique()
            ->values();
    }

    protected function teacherRecap(array $filters): Collection
    {
        $singleAttendances = Attendance::query()
            ->with('teachers')
            ->whereNull('attendance_batch_id')
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacherId) => $query->whereHas('teachers', fn ($teacherQuery) => $teacherQuery->whereKey($teacherId)))
            ->get();

        $groupBatches = AttendanceBatch::query()
            ->with('teachers')
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->whereHas('attendances', fn ($attendanceQuery) => $attendanceQuery->where('student_id', $studentId)))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacherId) => $query->whereHas('teachers', fn ($teacherQuery) => $teacherQuery->whereKey($teacherId)))
            ->get();

        $recap = collect();

        foreach ($singleAttendances as $attendance) {
            foreach ($attendance->teachers as $teacher) {
                $current = $recap->get($teacher->id, [
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->name,
                    'session_count' => 0,
                    'teaching_minutes' => 0,
                ]);

                $current['session_count']++;
                $current['teaching_minutes'] += $this->resolveTeachingMinutes($attendance->teaching_minutes);

                $recap->put($teacher->id, $current);
            }
        }

        foreach ($groupBatches as $batch) {
            foreach ($batch->teachers as $teacher) {
                $current = $recap->get($teacher->id, [
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->name,
                    'session_count' => 0,
                    'teaching_minutes' => 0,
                ]);

                $current['session_count']++;
                $current['teaching_minutes'] += $this->resolveTeachingMinutes($batch->teaching_minutes);

                $recap->put($teacher->id, $current);
            }
        }

        return $recap
            ->values()
            ->map(function (array $row) {
                $row['teaching_duration_label'] = $this->formatTeachingMinutes($row['teaching_minutes']);

                return (object) $row;
            })
            ->sortBy('teacher_name')
            ->values();
    }

    protected function formatTeachingMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$remainingMinutes}m";
    }

    protected function resolveTeachingMinutes($minutes): int
    {
        $resolved = (int) $minutes;

        return $resolved > 0 ? $resolved : 60;
    }

    protected function attendanceTableHasTeachingMinutes(): bool
    {
        static $hasColumn;

        return $hasColumn ??= Schema::hasColumn('attendances', 'teaching_minutes');
    }

    protected function attendanceBatchTableHasTeachingMinutes(): bool
    {
        static $hasColumn;

        return $hasColumn ??= Schema::hasColumn('attendance_batches', 'teaching_minutes');
    }
}
