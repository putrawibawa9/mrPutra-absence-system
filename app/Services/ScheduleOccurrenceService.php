<?php

namespace App\Services;

use App\Models\ScheduleChange;
use App\Models\TeacherLeave;
use App\Models\TeacherSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Meng-"expand" template jadwal mingguan (TeacherSchedule) ke occurrence per
 * tanggal nyata, lalu menerapkan pengecualian (ScheduleChange): guru pengganti,
 * pindah tanggal, atau pembatalan. Juga menandai guru yang sedang libur tapi
 * belum ada pengganti.
 */
class ScheduleOccurrenceService
{
    public const STATUS_NORMAL = 'normal';
    public const STATUS_SUBSTITUTE = 'substitute';
    public const STATUS_MOVED_OUT = 'moved_out';
    public const STATUS_MOVED_IN = 'moved_in';
    public const STATUS_RETIMED = 'retimed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_LEAVE_UNASSIGNED = 'leave_unassigned';

    /**
     * @return Collection<int, object> daftar occurrence (urut tanggal lalu jam).
     */
    public function forRange(Carbon $start, Carbon $end): Collection
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        // Batas datetime penuh karena kolom date disimpan sebagai "Y-m-d 00:00:00".
        $lo = $start->copy()->startOfDay()->toDateTimeString();
        $hi = $end->copy()->endOfDay()->toDateTimeString();

        $schedules = TeacherSchedule::query()
            ->where('is_active', true)
            ->with(['teacher:id,name', 'coTeacher:id,name', 'classroom.students:id,name,phone'])
            ->get()
            ->groupBy('day_of_week');

        // Pengecualian yang menyentuh rentang ini (lewat tanggal asli / tanggal pindahan).
        $changes = ScheduleChange::query()
            ->where(fn ($q) => $q->whereBetween('date', [$lo, $hi])->orWhereBetween('moved_to_date', [$lo, $hi]))
            ->with(['substituteTeacher:id,name', 'schedule.teacher:id,name', 'schedule.coTeacher:id,name', 'schedule.classroom.students:id,name,phone'])
            ->get();

        $changeByScheduleDate = [];
        foreach ($changes as $change) {
            $changeByScheduleDate[$change->teacher_schedule_id][$change->date->toDateString()] = $change;
        }

        // Guru yang libur (approved) per tanggal.
        $leaveByDate = TeacherLeave::query()
            ->where('status', TeacherLeave::STATUS_APPROVED)
            ->whereBetween('date', [$lo, $hi])
            ->get(['teacher_id', 'date'])
            ->groupBy(fn ($leave) => $leave->date->toDateString())
            ->map(fn ($rows) => $rows->pluck('teacher_id')->map(fn ($id) => (int) $id)->unique()->all());

        $occurrences = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->toDateString();
            $weekday = strtolower($date->englishDayOfWeek);

            foreach ($schedules->get($weekday, collect()) as $schedule) {
                $change = $changeByScheduleDate[$schedule->id][$dateStr] ?? null;
                $occurrences->push($this->buildOccurrence($schedule, $date->copy(), $change, $leaveByDate->get($dateStr, [])));
            }
        }

        // Occurrence hasil pindahan (muncul di tanggal baru).
        foreach ($changes as $change) {
            if (! $change->moved_to_date || ! $change->schedule) {
                continue;
            }
            if ($change->moved_to_date->lt($start) || $change->moved_to_date->gt($end)) {
                continue;
            }

            $schedule = $change->schedule;
            $occurrences->push((object) [
                'date' => $change->moved_to_date->copy(),
                'schedule' => $schedule,
                'classroom' => $schedule->classroom,
                'students' => $schedule->classroom?->students->pluck('name')->join(', '),
                'effective_teacher' => $change->substituteTeacher ?? $schedule->teacher,
                'original_teacher' => $change->substitute_teacher_id ? $schedule->teacher : null,
                'co_teacher' => $schedule->coTeacher,
                'start' => substr((string) ($change->moved_start_time ?? $schedule->start_time), 0, 5),
                'end' => substr((string) ($change->moved_end_time ?? $schedule->end_time), 0, 5),
                'status' => self::STATUS_MOVED_IN,
                'note' => 'Pindahan dari '.$change->date->translatedFormat('d M'),
                'change' => $change,
            ]);
        }

        return $occurrences
            ->sortBy([['date', 'asc'], ['start', 'asc']])
            ->values();
    }

    /** Occurrence untuk satu tanggal (dashboard / kelas hari ini). */
    public function forDate(Carbon $date): Collection
    {
        return $this->forRange($date, $date);
    }

    private function buildOccurrence(TeacherSchedule $schedule, Carbon $date, ?ScheduleChange $change, array $teachersOnLeave): object
    {
        $base = (object) [
            'date' => $date,
            'schedule' => $schedule,
            'classroom' => $schedule->classroom,
            'students' => $schedule->classroom?->students->pluck('name')->join(', '),
            'effective_teacher' => $schedule->teacher,
            'original_teacher' => null,
            'co_teacher' => $schedule->coTeacher,
            'start' => substr((string) $schedule->start_time, 0, 5),
            'end' => substr((string) $schedule->end_time, 0, 5),
            'status' => self::STATUS_NORMAL,
            'note' => null,
            'change' => $change,
        ];

        if ($change) {
            if ($change->is_cancelled) {
                $base->status = self::STATUS_CANCELLED;
                $base->note = 'Dibatalkan'.($change->reason ? ' — '.$change->reason : '');

                return $base;
            }

            if ($change->moved_to_date) {
                $base->status = self::STATUS_MOVED_OUT;
                $base->note = 'Dipindah ke '.$change->moved_to_date->translatedFormat('d M');

                return $base;
            }

            if ($change->substitute_teacher_id) {
                $base->status = self::STATUS_SUBSTITUTE;
                $base->effective_teacher = $change->substituteTeacher;
                $base->original_teacher = $schedule->teacher;
                $base->note = $change->reason ?: 'Guru pengganti';

                return $base;
            }

            // Ganti jam saja, tanggal tetap.
            if ($change->moved_start_time || $change->moved_end_time) {
                $origStart = substr((string) $schedule->start_time, 0, 5);
                $origEnd = substr((string) $schedule->end_time, 0, 5);
                $base->status = self::STATUS_RETIMED;
                $base->start = substr((string) ($change->moved_start_time ?? $schedule->start_time), 0, 5);
                $base->end = substr((string) ($change->moved_end_time ?? $schedule->end_time), 0, 5);
                $base->note = 'Jam diubah (dari '.$origStart.'–'.$origEnd.')';

                return $base;
            }
        }

        // Tidak ada change tapi guru asli sedang libur → perlu pengganti.
        if ($schedule->teacher_id && in_array((int) $schedule->teacher_id, $teachersOnLeave, true)) {
            $base->status = self::STATUS_LEAVE_UNASSIGNED;
            $base->note = 'Guru libur — belum ada pengganti';
        }

        return $base;
    }
}
