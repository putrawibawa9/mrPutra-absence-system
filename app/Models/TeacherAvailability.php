<?php

namespace App\Models;

use App\Support\WeeklyDay;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'teacher_id',
    'day_of_week',
    'start_time',
    'end_time',
    'status',
    'is_active',
    'notes',
])]
class TeacherAvailability extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_UNAVAILABLE = 'unavailable';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function dayLabel(): string
    {
        return WeeklyDay::label($this->day_of_week);
    }

    public function timeRangeLabel(): string
    {
        return substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5);
    }

    public function statusLabel(): string
    {
        return $this->status === self::STATUS_UNAVAILABLE ? 'Unavailable' : 'Available';
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_UNAVAILABLE => 'Unavailable',
        ];
    }
}
