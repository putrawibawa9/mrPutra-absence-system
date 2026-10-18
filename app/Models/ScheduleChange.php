<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'teacher_schedule_id',
    'date',
    'substitute_teacher_id',
    'moved_to_date',
    'moved_start_time',
    'moved_end_time',
    'is_cancelled',
    'reason',
    'teacher_leave_id',
    'created_by_user_id',
])]
class ScheduleChange extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'moved_to_date' => 'date',
            'is_cancelled' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TeacherSchedule::class, 'teacher_schedule_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function leave(): BelongsTo
    {
        return $this->belongsTo(TeacherLeave::class, 'teacher_leave_id');
    }

    /** Apakah change ini efektif tidak mengubah apa pun (boleh dihapus). */
    public function isEmpty(): bool
    {
        return ! $this->substitute_teacher_id
            && ! $this->moved_to_date
            && ! $this->is_cancelled;
    }
}
