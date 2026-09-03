<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomRequest;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassroomController extends Controller
{
    public function __construct(
        protected AttendanceTeacherFeeService $teacherFeeService,
        protected TokenService $tokenService,
    ) {
    }

    public function index()
    {
        $classrooms = Classroom::query()
            ->with('students:id,name')
            ->withCount('students')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $students = Student::active()->orderBy('name')->get(['id', 'name', 'phone']);
        $takenStudents = $this->takenStudentMap();

        return view('classrooms.create', compact('students', 'takenStudents'));
    }

    public function store(ClassroomRequest $request)
    {
        $data = $request->validated();

        $classroom = Classroom::create([
            'name' => $this->resolveName($data),
            'division' => $data['division'],
            'format' => $data['format'],
            'learning_mode' => $data['learning_mode'] ?? null,
            'age_group' => $data['age_group'],
            'level' => $data['level'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);
        $classroom->students()->sync(collect($data['student_ids'])->unique()->values());

        return redirect()->route('classrooms.index')->with('status', 'Kelas berhasil dibuat.');
    }

    public function edit(Classroom $classroom)
    {
        $classroom->load('students:id,name');
        $students = Student::active()->orderBy('name')->get(['id', 'name', 'phone']);
        $selectedStudentIds = $classroom->students->pluck('id')->all();
        $takenStudents = $this->takenStudentMap($classroom->id);

        return view('classrooms.edit', compact('classroom', 'students', 'selectedStudentIds', 'takenStudents'));
    }

    public function update(ClassroomRequest $request, Classroom $classroom)
    {
        $data = $request->validated();

        $classroom->update([
            'name' => $this->resolveName($data),
            'division' => $data['division'],
            'format' => $data['format'],
            'learning_mode' => $data['learning_mode'] ?? null,
            'age_group' => $data['age_group'],
            'level' => $data['level'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);
        $classroom->students()->sync(collect($data['student_ids'])->unique()->values());

        return redirect()->route('classrooms.index')->with('status', 'Kelas berhasil diperbarui.');
    }

    public function toggleStatus(Classroom $classroom)
    {
        $classroom->update(['is_active' => ! $classroom->is_active]);

        return redirect()->back()->with('status', $classroom->is_active ? 'Kelas diaktifkan.' : 'Kelas dinonaktifkan.');
    }

    public function destroy(Classroom $classroom)
    {
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('status', 'Kelas dihapus. Riwayat absensi tidak terpengaruh.');
    }

    public function createAttendance(Classroom $classroom)
    {
        abort_unless($classroom->is_active, 404);

        $classroom->load([
            'students' => fn ($query) => $query
                ->with(['payments' => fn ($payment) => $payment->where('remaining_sessions', '>', 0)])
                ->withCount(['attendances as token_debt_count' => fn ($debt) => $debt->whereNull('payment_id')]),
        ]);
        $teachers = User::teachers()->orderBy('name')->get();
        $materialLinks = MaterialLink::query()->where('is_active', true)->orderBy('title')->get();

        return view('classrooms.attendance', compact('classroom', 'teachers', 'materialLinks'));
    }

    public function storeAttendance(Request $request, Classroom $classroom)
    {
        abort_unless($classroom->is_active, 404);

        $request->validate([
            'date' => ['required', 'date'],
            'teacher_ids' => ['required', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_TEACHER))],
            'present_student_ids' => ['array'],
            'present_student_ids.*' => ['integer'],
            'learning_journal' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'teaching_minutes' => ['nullable', 'integer', 'min:0'],
            'material_link_ids' => ['array'],
            'material_link_ids.*' => ['integer', Rule::exists('material_links', 'id')],
        ], [
            'learning_journal.required' => 'Jurnal belajar wajib diisi.',
        ]);

        $memberIds = $classroom->students()->pluck('students.id');
        $presentIds = collect($request->input('present_student_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn ($id) => $memberIds->contains($id))
            ->values();

        if ($presentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'present_student_ids' => 'Pilih minimal satu murid yang hadir.',
            ]);
        }

        if ($classroom->isPrivate() && $presentIds->count() > 1) {
            throw ValidationException::withMessages([
                'present_student_ids' => 'Kelas private hanya untuk 1 murid.',
            ]);
        }

        $teacherIds = $this->resolveTeacherIds(
            $request->input('teacher_ids', []),
            $request->user()->isTeacher() ? $request->user()->id : null,
        );
        $primaryTeacherId = $this->resolvePrimaryTeacherId($teacherIds);
        $materialLinkIds = collect($request->input('material_link_ids', []))
            ->map(fn ($id) => (int) $id)->unique()->values();
        $date = $request->date('date');
        $journal = $request->string('learning_journal')->toString() ?: null;
        $notes = $request->string('notes')->toString() ?: null;
        // Durasi tidak lagi diisi di form; pakai default 60 menit agar data tetap konsisten.
        $minutes = $request->filled('teaching_minutes') ? (int) $request->integer('teaching_minutes') : 60;

        $absentIds = $memberIds
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $presentIds->contains($id))
            ->values();

        DB::transaction(function () use ($classroom, $presentIds, $absentIds, $teacherIds, $primaryTeacherId, $materialLinkIds, $date, $journal, $notes, $minutes, $request): void {
            if ($classroom->isPrivate()) {
                $studentId = (int) $presentIds->first();
                $payment = $this->resolvePayment($studentId, $classroom->division, $classroom->format);

                $attendance = Attendance::create([
                    'student_id' => $studentId,
                    'teacher_id' => $primaryTeacherId,
                    'payment_id' => $payment?->id,
                    'material_link_id' => $materialLinkIds->first(),
                    'date' => $date,
                    'teaching_minutes' => $minutes,
                    'notes' => $notes,
                    'learning_journal' => $journal,
                ]);
                $attendance->teachers()->sync($teacherIds);
                $attendance->materialLinks()->sync($materialLinkIds);

                if ($payment) {
                    $this->tokenService->consume($payment, $attendance, $date);
                }

                $attendance->load(['teachers', 'student']);
                $this->teacherFeeService->syncAttendance($attendance, $teacherIds, $request->user()->id);

                return;
            }

            $batch = AttendanceBatch::create([
                'title' => $classroom->name,
                'teacher_id' => $primaryTeacherId,
                'material_link_id' => $materialLinkIds->first(),
                'date' => $date,
                'teaching_minutes' => $minutes,
                'notes' => $notes,
                'learning_journal' => $journal,
            ]);
            $batch->teachers()->sync($teacherIds);
            $batch->materialLinks()->sync($materialLinkIds);

            foreach ($presentIds as $studentId) {
                $payment = $this->resolvePayment((int) $studentId, $classroom->division, $classroom->format);

                $attendance = Attendance::create([
                    'attendance_batch_id' => $batch->id,
                    'student_id' => (int) $studentId,
                    'teacher_id' => $primaryTeacherId,
                    'payment_id' => $payment?->id,
                    'material_link_id' => $materialLinkIds->first(),
                    'date' => $date,
                    'teaching_minutes' => $minutes,
                    'notes' => $notes,
                    'learning_journal' => $journal,
                ]);
                $attendance->materialLinks()->sync($materialLinkIds);

                if ($payment) {
                    $this->tokenService->consume($payment, $attendance, $date);
                }
            }

            // Sesi grup: begitu sesi tercatat (batch dibuat), setiap anggota kelas
            // yang tidak hadir tetap kehilangan 1 token — berlaku untuk semua mode
            // (synchronous maupun self-paced). Tidak ada baris Attendance untuk yang
            // absen; token cukup di-forfeit di ledger, dan cash-flow menghitung token
            // forfeited sebagai pendapatan (peserta absen = 1 token = 1 sesi terbayar).
            // Kelas private tidak masuk sini: kalau muridnya tidak datang, sesi tidak
            // dicatat sama sekali (validasi butuh minimal 1 murid hadir).
            foreach ($absentIds as $studentId) {
                $payment = $this->resolvePayment((int) $studentId, $classroom->division, $classroom->format);

                if ($payment) {
                    $this->tokenService->forfeit($payment, $batch, $date);
                }
            }

            $batch->load('teachers');
            $this->teacherFeeService->syncBatch($batch, $teacherIds, $request->user()->id);
        });

        $statusNote = $classroom->isPrivate()
            ? 'Token murid terpotong.'
            : 'Token murid yang hadir & yang tidak hadir terpotong.';

        return redirect()->route('attendances.index')
            ->with('status', 'Absensi kelas "'.$classroom->name.'" tersimpan. '.$statusNote);
    }

    /**
     * Peta murid yang sudah jadi anggota kelas aktif lain: [student_id => nama_kelas].
     */
    protected function takenStudentMap(?int $excludeClassroomId = null): array
    {
        // NB: dibangun manual (bukan flatMap) karena flatMap me-reindex kunci
        // integer, sehingga map jadi ber-key posisi, bukan student_id.
        $map = [];

        Classroom::query()
            ->where('is_active', true)
            ->when($excludeClassroomId, fn ($query) => $query->whereKeyNot($excludeClassroomId))
            ->with('students:id,name')
            ->get()
            ->each(function (Classroom $classroom) use (&$map): void {
                foreach ($classroom->students as $student) {
                    $map[$student->id] = $classroom->name;
                }
            });

        return $map;
    }

    protected function resolveName(array $data): string
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name !== '') {
            return $name;
        }

        return Classroom::makeName($data['division'] ?? null, $data['format'] ?? null, $data['age_group'] ?? null);
    }

    protected function resolvePayment(int $studentId, ?string $division = null, ?string $format = null): ?Payment
    {
        return Payment::query()
            ->where('student_id', $studentId)
            ->where('remaining_sessions', '>', 0)
            // Scope tokens to the class division/format; null on a payment is a
            // wildcard (legacy paket) so it can still be spent anywhere.
            ->when($division, fn ($query, $division) => $query->where(fn ($query) => $query->whereNull('division')->orWhere('division', $division)))
            ->when($format, fn ($query, $format) => $query->where(fn ($query) => $query->whereNull('format')->orWhere('format', $format)))
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    protected function resolveTeacherIds(array $requestedTeacherIds, ?int $fallbackTeacherId = null): Collection
    {
        return collect($requestedTeacherIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->when($fallbackTeacherId, fn ($teacherIds) => $teacherIds->push($fallbackTeacherId))
            ->unique()
            ->values();
    }

    protected function resolvePrimaryTeacherId(Collection $teacherIds): int
    {
        $primaryTeacherId = $teacherIds->first();

        if (! $primaryTeacherId) {
            throw ValidationException::withMessages([
                'teacher_ids' => 'Minimal satu guru harus dipilih.',
            ]);
        }

        return (int) $primaryTeacherId;
    }
}
