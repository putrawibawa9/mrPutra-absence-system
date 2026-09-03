<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Classroom;
use App\Models\MaterialLink;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceTeacherFeeService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceTeacherFeeService $teacherFeeService,
        protected TokenService $tokenService,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'exists:students,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'teacher_fee_status' => ['nullable', 'in:with_fee,without_fee'],
        ]);

        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
            $filters['date_to'] = now()->endOfMonth()->toDateString();
        }

        $attendanceRows = Attendance::with(['teachers', 'expenses', 'batch.teachers', 'batch.expenses', 'batch.materialLinks', 'batch.materialLink', 'student', 'teacher', 'payment', 'materialLinks', 'materialLink'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('learning_journal', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%')
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('teacher', fn ($teacherQuery) => $teacherQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('teachers', fn ($teacherQuery) => $teacherQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('materialLinks', function ($materialQuery) use ($search) {
                            $materialQuery->where('title', 'like', '%'.$search.'%')
                                ->orWhere('url', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('batch', function ($batchQuery) use ($search) {
                            $batchQuery->where('title', 'like', '%'.$search.'%')
                                ->orWhereHas('materialLinks', function ($materialQuery) use ($search) {
                                    $materialQuery->where('title', 'like', '%'.$search.'%')
                                        ->orWhere('url', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($filters['teacher_id'] ?? null, function ($query, $teacherId) {
                $query->where(function ($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId)
                        ->orWhereHas('teachers', fn ($attendanceQuery) => $attendanceQuery->whereKey($teacherId))
                        ->orWhereHas('batch.teachers', fn ($batchQuery) => $batchQuery->whereKey($teacherId));
                });
            })
            ->latest('date')
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
                    $batch = $first->batch;
                    $hasTeacherFee = $batch ? $this->teacherFeeService->hasCompleteBatchFee($batch) : false;

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
                        'material_links' => $batch?->materialLinks ?? collect(),
                        'material_link_label' => $batch?->materialLinkLabels() ?? '-',
                        'teacher_fee_status' => $hasTeacherFee ? 'with_fee' : 'without_fee',
                        'teacher_fee_label' => $hasTeacherFee ? 'Sudah' : 'Belum',
                        'teacher_fee_badge_class' => $hasTeacherFee ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                        'learning_journal' => $first->learning_journal,
                        'notes' => $first->notes ?: '-',
                        'editable' => true,
                        'attendance' => null,
                        'attendance_batch' => $first->batch,
                    ];
                }

                $teacherNames = collect([$first->teacher->name])
                    ->merge($first->teachers->pluck('name'))
                    ->filter()
                    ->unique()
                    ->values();
                $hasTeacherFee = $this->teacherFeeService->hasCompleteAttendanceFee($first);

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
                    'material_links' => $first->materialLinks,
                    'material_link_label' => $first->materialLinkLabels(),
                    'teacher_fee_status' => $hasTeacherFee ? 'with_fee' : 'without_fee',
                    'teacher_fee_label' => $hasTeacherFee ? 'Sudah' : 'Belum',
                    'teacher_fee_badge_class' => $hasTeacherFee ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                    'learning_journal' => $first->learning_journal,
                    'notes' => $first->notes ?: '-',
                    'editable' => true,
                    'attendance' => $first,
                    'attendance_batch' => null,
                ];
            })
            ->when($filters['teacher_fee_status'] ?? null, fn ($entries, $status) => $entries->where('teacher_fee_status', $status))
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
        // Absensi kini berbasis kelas: pilih kelas, lalu centang murid yang hadir.
        // Nama murid ikut dimuat agar pencarian bisa via nama murid (hasil tetap kelas).
        $classrooms = Classroom::query()
            ->active()
            ->with('students:id,name')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return view('attendances.create', compact('classrooms'));
    }

    public function edit(Request $request, Attendance $attendance)
    {
        abort_if($attendance->attendance_batch_id, 403, 'Group attendance entries cannot be edited individually.');

        $students = $this->attendanceFormData();
        $selectedStudent = $request->integer('student_id') ?: $attendance->student_id;
        $activePayments = $this->activePaymentsForStudent($selectedStudent, $attendance->payment_id);
        $activeSessions = (int) $activePayments->sum('remaining_sessions');

        $teachers = User::teachers()->orderBy('name')->get();
        $materialLinks = MaterialLink::query()->where('is_active', true)->orderBy('title')->get();

        return view('attendances.edit', compact('attendance', 'students', 'teachers', 'materialLinks', 'activePayments', 'activeSessions', 'selectedStudent'));
    }

    public function editBatch(AttendanceBatch $attendanceBatch)
    {
        $attendanceBatch->load([
            'teachers',
            'attendances.student.payments',
            'attendances.student.latestAttendance.teacher',
            'attendances.student.latestAttendance.batch',
        ]);

        $students = $this->attendanceFormData();
        $teachers = User::teachers()->orderBy('name')->get();
        $materialLinks = MaterialLink::query()->where('is_active', true)->orderBy('title')->get();

        return view('attendances.edit-batch', compact('attendanceBatch', 'students', 'teachers', 'materialLinks'));
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
            $materialLinkIds = $this->resolveMaterialLinkIds($request->input('material_link_ids', []), legacyMaterialLinkId: $request->integer('material_link_id'));
            $teacherIds = $this->resolveTeacherIds(
                requestedTeacherIds: $request->input('teacher_ids', []),
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );
            $primaryTeacherId = $this->resolvePrimaryTeacherId(
                teacherIds: $teacherIds,
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );

            $attendancePayload = [
                'student_id' => $request->integer('student_id'),
                'teacher_id' => $primaryTeacherId,
                'payment_id' => $payment?->id,
                'material_link_id' => $materialLinkIds->first(),
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $request->string('learning_journal')->toString(),
                'homework_content' => $this->nullableRequestString($request->input('homework_content')),
            ];

            if ($this->attendanceTableHasTeachingMinutes()) {
                $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $attendance = Attendance::create($attendancePayload);

            $attendance->teachers()->sync($teacherIds);
            $attendance->materialLinks()->sync($materialLinkIds);
            $attendance->load(['student', 'teachers']);
            $this->teacherFeeService->syncAttendance($attendance, $teacherIds, $request->user()->id);

            if ($payment) {
                $this->tokenService->consume($payment, $attendance, $request->date('date'));
            }
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
            $materialLinkIds = $this->resolveMaterialLinkIds($request->input('material_link_ids', []), legacyMaterialLinkId: $request->integer('material_link_id'));
            $teacherIds = $this->resolveTeacherIds(
                requestedTeacherIds: $request->input('teacher_ids', []),
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );
            $primaryTeacherId = $this->resolvePrimaryTeacherId(
                teacherIds: $teacherIds,
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );

            $attendancePayload = [
                'student_id' => $request->integer('student_id'),
                'teacher_id' => $primaryTeacherId,
                'payment_id' => $newPayment?->id,
                'material_link_id' => $materialLinkIds->first(),
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $request->string('learning_journal')->toString(),
                'homework_content' => $this->nullableRequestString($request->input('homework_content')),
            ];

            if ($this->attendanceTableHasTeachingMinutes()) {
                $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $attendance->update($attendancePayload);

            $attendance->teachers()->sync($teacherIds);
            $attendance->materialLinks()->sync($materialLinkIds);
            $attendance->load(['student', 'teachers']);
            $this->teacherFeeService->syncAttendance($attendance, $teacherIds, $request->user()->id);

            // Rebalance the token ledger only when the linked payment changed.
            if (($oldPayment?->id) !== ($newPayment?->id)) {
                if ($oldPayment) {
                    $this->tokenService->release($oldPayment, $attendance);
                }

                if ($newPayment) {
                    $this->tokenService->consume($newPayment, $attendance, $request->date('date'));
                }
            }
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
        $groupMaterialLinkIds = $this->resolveMaterialLinkIds($request->input('group_material_link_ids', []), legacyMaterialLinkId: $request->integer('group_material_link_id'));
        $groupJournalMode = $request->input('group_journal_mode', 'group');
        $groupLearningJournal = $request->string('learning_journal')->toString();
        $studentLearningJournals = collect($request->input('student_learning_journals', []));
        $groupHomeworkMode = $request->input('group_homework_mode', 'group');
        $groupHomeworkContent = $this->nullableRequestString($request->input('group_homework_content'));
        $studentHomeworkContents = collect($request->input('student_homework_contents', []));

        DB::transaction(function () use ($request, $studentIds, $groupMaterialLinkIds, $groupJournalMode, $groupLearningJournal, $studentLearningJournals, $groupHomeworkMode, $groupHomeworkContent, $studentHomeworkContents): void {
            $teacherIds = $this->resolveTeacherIds(
                requestedTeacherIds: $request->input('group_teacher_ids', []),
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );
            $primaryTeacherId = $this->resolvePrimaryTeacherId(
                teacherIds: $teacherIds,
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );
            $batchPayload = [
                'title' => $request->string('group_title')->toString(),
                'teacher_id' => $primaryTeacherId,
                'material_link_id' => $groupMaterialLinkIds->first(),
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $groupJournalMode === 'per_student'
                    ? 'Individual learning journals recorded for each student.'
                    : $groupLearningJournal,
            ];

            if ($this->attendanceBatchTableHasTeachingMinutes()) {
                $batchPayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $batch = AttendanceBatch::create($batchPayload);

            $batch->teachers()->sync($teacherIds);
            $batch->materialLinks()->sync($groupMaterialLinkIds);
            $batch->load('teachers');

            foreach ($studentIds as $studentId) {
                $payment = $this->resolvePaymentForAttendance($studentId);
                $studentLearningJournal = $groupJournalMode === 'per_student'
                    ? trim((string) $studentLearningJournals->get((string) $studentId, $studentLearningJournals->get($studentId, '')))
                    : $groupLearningJournal;
                $studentHomeworkContent = $groupHomeworkMode === 'per_student'
                    ? $this->nullableRequestString($studentHomeworkContents->get((string) $studentId, $studentHomeworkContents->get($studentId)))
                    : $groupHomeworkContent;

                $attendancePayload = [
                    'attendance_batch_id' => $batch->id,
                    'student_id' => $studentId,
                    'teacher_id' => $primaryTeacherId,
                    'payment_id' => $payment?->id,
                    'material_link_id' => $groupMaterialLinkIds->first(),
                    'date' => $request->date('date'),
                    'notes' => $request->string('notes')->toString(),
                    'learning_journal' => $studentLearningJournal,
                    'homework_content' => $studentHomeworkContent,
                ];

                if ($this->attendanceTableHasTeachingMinutes()) {
                    $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
                }

                $attendance = Attendance::create($attendancePayload);
                $attendance->materialLinks()->sync($groupMaterialLinkIds);

                if ($payment) {
                    $this->tokenService->consume($payment, $attendance, $request->date('date'));
                }
            }

            $this->teacherFeeService->syncBatch($batch, $teacherIds, $request->user()->id);
        });

        return redirect()->route('attendances.index')->with('status', 'Group attendance saved and sessions deducted for selected students.');
    }

    public function updateBatch(AttendanceRequest $request, AttendanceBatch $attendanceBatch)
    {
        $studentIds = collect($request->input('student_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $groupMaterialLinkIds = $this->resolveMaterialLinkIds($request->input('group_material_link_ids', []), legacyMaterialLinkId: $request->integer('group_material_link_id'));
        $groupJournalMode = $request->input('group_journal_mode', 'group');
        $groupLearningJournal = $request->string('learning_journal')->toString();
        $studentLearningJournals = collect($request->input('student_learning_journals', []));
        $groupHomeworkMode = $request->input('group_homework_mode', 'group');
        $groupHomeworkContent = $this->nullableRequestString($request->input('group_homework_content'));
        $studentHomeworkContents = collect($request->input('student_homework_contents', []));

        DB::transaction(function () use ($request, $attendanceBatch, $studentIds, $groupMaterialLinkIds, $groupJournalMode, $groupLearningJournal, $studentLearningJournals, $groupHomeworkMode, $groupHomeworkContent, $studentHomeworkContents): void {
            $attendanceBatch->load(['attendances', 'teachers']);

            $teacherIds = $this->resolveTeacherIds(
                requestedTeacherIds: $request->input('group_teacher_ids', []),
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );
            $primaryTeacherId = $this->resolvePrimaryTeacherId(
                teacherIds: $teacherIds,
                fallbackTeacherId: $request->user()->isTeacher() ? $request->user()->id : null,
            );

            $batchPayload = [
                'title' => $request->string('group_title')->toString(),
                'teacher_id' => $primaryTeacherId,
                'material_link_id' => $groupMaterialLinkIds->first(),
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
                'learning_journal' => $groupJournalMode === 'per_student'
                    ? 'Individual learning journals recorded for each student.'
                    : $groupLearningJournal,
            ];

            if ($this->attendanceBatchTableHasTeachingMinutes()) {
                $batchPayload['teaching_minutes'] = $request->integer('teaching_minutes');
            }

            $attendanceBatch->update($batchPayload);
            $attendanceBatch->teachers()->sync($teacherIds);
            $attendanceBatch->materialLinks()->sync($groupMaterialLinkIds);
            $attendanceBatch->load('teachers');

            $existingAttendances = $attendanceBatch->attendances->keyBy('student_id');
            $removedStudentIds = $existingAttendances->keys()->diff($studentIds);

            foreach ($removedStudentIds as $removedStudentId) {
                $attendance = $existingAttendances->get($removedStudentId);

                if ($attendance) {
                    if ($attendance->payment) {
                        $this->tokenService->release($attendance->payment, $attendance);
                    }
                    $attendance->delete();
                }
            }

            foreach ($studentIds as $studentId) {
                $studentLearningJournal = $groupJournalMode === 'per_student'
                    ? trim((string) $studentLearningJournals->get((string) $studentId, $studentLearningJournals->get($studentId, '')))
                    : $groupLearningJournal;
                $studentHomeworkContent = $groupHomeworkMode === 'per_student'
                    ? $this->nullableRequestString($studentHomeworkContents->get((string) $studentId, $studentHomeworkContents->get($studentId)))
                    : $groupHomeworkContent;

                $attendancePayload = [
                    'teacher_id' => $primaryTeacherId,
                    'material_link_id' => $groupMaterialLinkIds->first(),
                    'date' => $request->date('date'),
                    'notes' => $request->string('notes')->toString(),
                    'learning_journal' => $studentLearningJournal,
                    'homework_content' => $studentHomeworkContent,
                ];

                if ($this->attendanceTableHasTeachingMinutes()) {
                    $attendancePayload['teaching_minutes'] = $request->integer('teaching_minutes');
                }

                $existingAttendance = $existingAttendances->get($studentId);

                if ($existingAttendance) {
                    $existingAttendance->update($attendancePayload);
                    $existingAttendance->materialLinks()->sync($groupMaterialLinkIds);

                    continue;
                }

                $payment = $this->resolvePaymentForAttendance($studentId);

                $attendance = Attendance::create(array_merge($attendancePayload, [
                    'attendance_batch_id' => $attendanceBatch->id,
                    'student_id' => $studentId,
                    'payment_id' => $payment?->id,
                ]));
                $attendance->materialLinks()->sync($groupMaterialLinkIds);

                if ($payment) {
                    $this->tokenService->consume($payment, $attendance, $request->date('date'));
                }
            }

            $this->teacherFeeService->syncBatch($attendanceBatch, $teacherIds, $request->user()->id);
        });

        return redirect()->route('attendances.index')->with('status', 'Group attendance updated successfully.');
    }

    public function destroy(Attendance $attendance)
    {
        abort_if($attendance->attendance_batch_id, 403, 'Absensi grup dihapus lewat tombol Hapus pada barisnya.');

        DB::transaction(function () use ($attendance): void {
            // Kembalikan token yang dipakai (kalau bukan token debt).
            if ($attendance->payment) {
                $this->tokenService->release($attendance->payment, $attendance);
            }

            // Hapus attendance — fee guru (FK attendance_id) & pivot ikut terhapus (cascade).
            $attendance->delete();
        });

        return redirect()->route('attendances.index')
            ->with('status', 'Absensi dihapus. Token murid dikembalikan & fee guru terkait ikut dihapus.');
    }

    public function destroyBatch(AttendanceBatch $attendanceBatch)
    {
        DB::transaction(function () use ($attendanceBatch): void {
            // Kembalikan semua token batch ini: yang hadir (consumed) & yang bolos (forfeited).
            $this->tokenService->releaseBatch($attendanceBatch);

            // attendance_batch_id = nullOnDelete, jadi tiap absensi dihapus manual
            // (fee guru per-attendance & pivot ikut terhapus via cascade).
            $attendanceBatch->attendances()->get()->each(fn (Attendance $attendance) => $attendance->delete());

            // Hapus batch — fee guru batch & pivot guru/materi batch ikut terhapus (cascade).
            $attendanceBatch->delete();
        });

        return redirect()->route('attendances.index')
            ->with('status', 'Absensi kelas dihapus. Token semua murid dikembalikan & fee guru terkait ikut dihapus.');
    }

    protected function attendanceFormData()
    {
        return Student::active()
            ->with([
                'payments' => fn ($query) => $query->active(),
                'latestAttendance.teacher',
                'latestAttendance.batch',
                'latestHomeworkAttendance.teacher',
                'latestHomeworkAttendance.batch',
                'attendances' => fn ($query) => $query
                    ->where(function ($query) {
                        $query->whereNotNull('material_link_id')
                            ->orWhereHas('materialLinks');
                    })
                    ->with(['materialLinks:id,title,url', 'materialLink:id,title,url', 'teacher:id,name'])
                    ->latest('date'),
            ])
            ->orderBy('name')
            ->get();
    }

    protected function activePaymentsForStudent(int $studentId, ?int $includePaymentId = null)
    {
        return Payment::query()
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

    protected function resolveTeacherIds(array $requestedTeacherIds, ?int $fallbackTeacherId = null)
    {
        return collect($requestedTeacherIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->when($fallbackTeacherId, fn ($teacherIds) => $teacherIds->push($fallbackTeacherId))
            ->unique()
            ->values();
    }

    protected function resolvePrimaryTeacherId(Collection $teacherIds, ?int $fallbackTeacherId = null): int
    {
        $primaryTeacherId = $teacherIds->first() ?: $fallbackTeacherId;

        if (! $primaryTeacherId) {
            throw ValidationException::withMessages([
                'teacher_ids' => 'At least one teacher must be selected for this attendance.',
            ]);
        }

        return (int) $primaryTeacherId;
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

    protected function resolveMaterialLinkIds(array $requestedMaterialLinkIds, ?int $legacyMaterialLinkId = null): Collection
    {
        return collect($requestedMaterialLinkIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->when($legacyMaterialLinkId, fn ($materialLinkIds) => $materialLinkIds->push($legacyMaterialLinkId))
            ->unique()
            ->values();
    }

    protected function nullableRequestString(mixed $value): ?string
    {
        $resolved = trim((string) $value);

        return $resolved === '' ? null : $resolved;
    }

}
