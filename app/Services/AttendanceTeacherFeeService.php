<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceTeacherFeeService
{
    public const FEE_PER_MEETING = 40000;
    public const CATEGORY_NAME = 'Fee Guru';
    public const ROLE_CO_TEACHER = 'co_teacher';

    public function syncAttendance(Attendance $attendance, Collection $teacherIds, int $actorId): void
    {
        $category = $this->category();
        $item = $this->item($category);
        $teacherIds = $teacherIds
            ->filter()
            ->map(fn ($teacherId) => (int) $teacherId)
            ->unique()
            ->values();

        Expense::query()
            ->where('attendance_id', $attendance->id)
            ->whereNotIn('teacher_user_id', $teacherIds)
            ->delete();

        $pivots = DB::table('attendance_teacher')
            ->where('attendance_id', $attendance->id)
            ->get()
            ->keyBy('teacher_id');

        foreach ($teacherIds as $teacherId) {
            $pivot = $pivots->get($teacherId);
            $amount = $this->resolveFee($pivot);
            $isCoTeacher = $pivot && $pivot->role === self::ROLE_CO_TEACHER;

            // Co-teacher yang salary-nya belum ditentukan (0) tidak menghasilkan
            // expense — tapi tetap tercatat sebagai co-teacher di pivot.
            if ($amount <= 0) {
                Expense::query()
                    ->where('attendance_id', $attendance->id)
                    ->where('teacher_user_id', $teacherId)
                    ->delete();

                continue;
            }

            $teacher = ($attendance->relationLoaded('teachers') ? $attendance->teachers->firstWhere('id', $teacherId) : null)
                ?? User::query()->find($teacherId);

            Expense::query()->updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'teacher_user_id' => $teacherId,
                ],
                [
                    'expense_category_id' => $category->id,
                    'expense_item_id' => $item->id,
                    'attendance_batch_id' => null,
                    'created_by_user_id' => $actorId,
                    'title' => ($isCoTeacher ? 'Fee co-teacher - ' : 'Fee guru - ').($teacher?->name ?? 'Teacher').' - '.$attendance->student->name,
                    'amount' => $amount,
                    'expense_date' => $attendance->date,
                    'notes' => $isCoTeacher ? 'Fee co-teacher (custom) dari attendance.' : 'Otomatis dari attendance siswa.',
                ]
            );
        }
    }

    public function syncBatch(AttendanceBatch $attendanceBatch, Collection $teacherIds, int $actorId): void
    {
        $category = $this->category();
        $item = $this->item($category);
        $teacherIds = $teacherIds
            ->filter()
            ->map(fn ($teacherId) => (int) $teacherId)
            ->unique()
            ->values();

        Expense::query()
            ->where('attendance_batch_id', $attendanceBatch->id)
            ->whereNotIn('teacher_user_id', $teacherIds)
            ->delete();

        $pivots = DB::table('attendance_batch_teacher')
            ->where('attendance_batch_id', $attendanceBatch->id)
            ->get()
            ->keyBy('teacher_id');

        foreach ($teacherIds as $teacherId) {
            $pivot = $pivots->get($teacherId);
            $amount = $this->resolveFee($pivot);
            $isCoTeacher = $pivot && $pivot->role === self::ROLE_CO_TEACHER;

            if ($amount <= 0) {
                Expense::query()
                    ->where('attendance_batch_id', $attendanceBatch->id)
                    ->where('teacher_user_id', $teacherId)
                    ->delete();

                continue;
            }

            $teacher = ($attendanceBatch->relationLoaded('teachers') ? $attendanceBatch->teachers->firstWhere('id', $teacherId) : null)
                ?? User::query()->find($teacherId);

            Expense::query()->updateOrCreate(
                [
                    'attendance_batch_id' => $attendanceBatch->id,
                    'teacher_user_id' => $teacherId,
                ],
                [
                    'expense_category_id' => $category->id,
                    'expense_item_id' => $item->id,
                    'attendance_id' => null,
                    'created_by_user_id' => $actorId,
                    'title' => ($isCoTeacher ? 'Fee co-teacher - ' : 'Fee guru - ').($teacher?->name ?? 'Teacher').' - '.($attendanceBatch->title ?: 'Group Class'),
                    'amount' => $amount,
                    'expense_date' => $attendanceBatch->date,
                    'notes' => $isCoTeacher ? 'Fee co-teacher (custom) dari attendance batch.' : 'Otomatis dari attendance batch.',
                ]
            );
        }
    }

    /**
     * Fee efektif dari baris pivot: fee_amount NULL = fee standar; selain itu
     * pakai nilai custom (bisa 0 = belum ada salary).
     */
    private function resolveFee(?object $pivot): int
    {
        if ($pivot && $pivot->fee_amount !== null) {
            return (int) $pivot->fee_amount;
        }

        return static::FEE_PER_MEETING;
    }

    public function backfill(?int $actorId = null): array
    {
        $syncedSingles = 0;
        $syncedBatches = 0;

        Attendance::query()
            ->whereNull('attendance_batch_id')
            ->with(['student', 'teachers', 'expenses'])
            ->chunkById(100, function ($attendances) use (&$syncedSingles, $actorId): void {
                foreach ($attendances as $attendance) {
                    $teacherIds = $attendance->teachers->pluck('id')
                        ->whenEmpty(fn ($teacherIds) => $attendance->teacher_id ? $teacherIds->push($attendance->teacher_id) : $teacherIds)
                        ->values();

                    if ($teacherIds->isEmpty()) {
                        continue;
                    }

                    $this->syncAttendance($attendance, $teacherIds, $actorId ?? $attendance->teacher_id ?? 1);
                    $syncedSingles++;
                }
            });

        AttendanceBatch::query()
            ->with(['teachers', 'expenses'])
            ->chunkById(100, function ($batches) use (&$syncedBatches, $actorId): void {
                foreach ($batches as $batch) {
                    $teacherIds = $batch->teachers->pluck('id')
                        ->whenEmpty(fn ($teacherIds) => $batch->teacher_id ? $teacherIds->push($batch->teacher_id) : $teacherIds)
                        ->values();

                    if ($teacherIds->isEmpty()) {
                        continue;
                    }

                    $this->syncBatch($batch, $teacherIds, $actorId ?? $batch->teacher_id ?? 1);
                    $syncedBatches++;
                }
            });

        return [
            'single_attendances' => $syncedSingles,
            'batch_attendances' => $syncedBatches,
        ];
    }

    public function hasCompleteAttendanceFee(Attendance $attendance): bool
    {
        // Guru yang MEMBUTUHKAN fee expense: fee_amount null (pakai standar) atau > 0.
        // Co-teacher dengan fee 0 (belum ditentukan) tidak dihitung.
        $teacherIds = $attendance->teachers
            ->filter(fn ($teacher) => $this->pivotNeedsFee($teacher->pivot ?? null))
            ->pluck('id')
            ->whenEmpty(fn ($teacherIds) => $attendance->teacher_id ? $teacherIds->push($attendance->teacher_id) : $teacherIds)
            ->unique()
            ->values();

        if ($teacherIds->isEmpty()) {
            return false;
        }

        $expenseTeacherIds = $attendance->expenses->pluck('teacher_user_id')
            ->filter()
            ->map(fn ($teacherId) => (int) $teacherId)
            ->unique()
            ->values();

        return $teacherIds->diff($expenseTeacherIds)->isEmpty();
    }

    /** Baris pivot butuh fee bila fee_amount null (standar) atau > 0. */
    private function pivotNeedsFee($pivot): bool
    {
        if (! $pivot) {
            return true;
        }

        $fee = $pivot->fee_amount ?? null;

        return $fee === null || (int) $fee > 0;
    }

    public function hasCompleteBatchFee(AttendanceBatch $attendanceBatch): bool
    {
        $teacherIds = $attendanceBatch->teachers
            ->filter(fn ($teacher) => $this->pivotNeedsFee($teacher->pivot ?? null))
            ->pluck('id')
            ->whenEmpty(fn ($teacherIds) => $attendanceBatch->teacher_id ? $teacherIds->push($attendanceBatch->teacher_id) : $teacherIds)
            ->unique()
            ->values();

        if ($teacherIds->isEmpty()) {
            return false;
        }

        $expenseTeacherIds = $attendanceBatch->expenses->pluck('teacher_user_id')
            ->filter()
            ->map(fn ($teacherId) => (int) $teacherId)
            ->unique()
            ->values();

        return $teacherIds->diff($expenseTeacherIds)->isEmpty();
    }

    protected function category(): ExpenseCategory
    {
        $category = ExpenseCategory::query()->firstOrCreate(
            ['name' => static::CATEGORY_NAME],
            [
                'notes' => 'Expense otomatis untuk fee guru per pertemuan.',
                'is_active' => true,
                'cost_behavior' => ExpenseCategory::COST_VARIABLE,
                'is_system' => true,
            ]
        );

        // Pastikan flag sistem menyala walau kategori sudah ada sebelumnya.
        if (! $category->is_system) {
            $category->forceFill(['is_system' => true])->save();
        }

        return $category;
    }

    /**
     * Item master "Fee Guru" (di kategori sistem). Fee guru otomatis selalu
     * memakai item ini agar konsisten dengan kategorisasi berbasis item.
     */
    protected function item(ExpenseCategory $category): ExpenseItem
    {
        return ExpenseItem::query()->firstOrCreate(
            ['name' => static::CATEGORY_NAME],
            [
                'expense_category_id' => $category->id,
                'keywords' => null,
                'is_active' => true,
            ]
        );
    }
}
