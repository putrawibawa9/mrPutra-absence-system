<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Support\Collection;

class AttendanceTeacherFeeService
{
    public const FEE_PER_MEETING = 40000;
    public const CATEGORY_NAME = 'Fee Guru';

    public function syncAttendance(Attendance $attendance, Collection $teacherIds, int $actorId): void
    {
        $category = $this->category();
        $teacherIds = $teacherIds
            ->filter()
            ->map(fn ($teacherId) => (int) $teacherId)
            ->unique()
            ->values();

        Expense::query()
            ->where('attendance_id', $attendance->id)
            ->whereNotIn('teacher_user_id', $teacherIds)
            ->delete();

        foreach ($teacherIds as $teacherId) {
            $teacher = $attendance->teachers->firstWhere('id', $teacherId) ?? User::query()->find($teacherId);

            Expense::query()->updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'teacher_user_id' => $teacherId,
                ],
                [
                    'expense_category_id' => $category->id,
                    'attendance_batch_id' => null,
                    'created_by_user_id' => $actorId,
                    'title' => 'Fee guru - '.($teacher?->name ?? 'Teacher').' - '.$attendance->student->name,
                    'amount' => static::FEE_PER_MEETING,
                    'expense_date' => $attendance->date,
                    'notes' => 'Otomatis dari attendance siswa.',
                ]
            );
        }
    }

    public function syncBatch(AttendanceBatch $attendanceBatch, Collection $teacherIds, int $actorId): void
    {
        $category = $this->category();
        $teacherIds = $teacherIds
            ->filter()
            ->map(fn ($teacherId) => (int) $teacherId)
            ->unique()
            ->values();

        Expense::query()
            ->where('attendance_batch_id', $attendanceBatch->id)
            ->whereNotIn('teacher_user_id', $teacherIds)
            ->delete();

        foreach ($teacherIds as $teacherId) {
            $teacher = $attendanceBatch->teachers->firstWhere('id', $teacherId) ?? User::query()->find($teacherId);

            Expense::query()->updateOrCreate(
                [
                    'attendance_batch_id' => $attendanceBatch->id,
                    'teacher_user_id' => $teacherId,
                ],
                [
                    'expense_category_id' => $category->id,
                    'attendance_id' => null,
                    'created_by_user_id' => $actorId,
                    'title' => 'Fee guru - '.($teacher?->name ?? 'Teacher').' - '.($attendanceBatch->title ?: 'Group Class'),
                    'amount' => static::FEE_PER_MEETING,
                    'expense_date' => $attendanceBatch->date,
                    'notes' => 'Otomatis dari attendance batch.',
                ]
            );
        }
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
        $teacherIds = $attendance->teachers->pluck('id')
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

    public function hasCompleteBatchFee(AttendanceBatch $attendanceBatch): bool
    {
        $teacherIds = $attendanceBatch->teachers->pluck('id')
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
        return ExpenseCategory::query()->firstOrCreate(
            ['name' => static::CATEGORY_NAME],
            [
                'notes' => 'Expense otomatis untuk fee guru per pertemuan.',
                'is_active' => true,
            ]
        );
    }
}
