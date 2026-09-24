<?php

namespace App\Models;

use App\Support\WeeklyDay;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'teacher_id',
    'date',
    'start_time',
    'end_time',
    'reason',
    'status',
    'reviewed_by_user_id',
    'reviewed_at',
])]
class TeacherLeave extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isWholeDay(): bool
    {
        return blank($this->start_time) || blank($this->end_time);
    }

    /** Hari dalam seminggu dari tanggal libur (untuk cocokkan ketersediaan mingguan). */
    public function weekdayKey(): string
    {
        return strtolower(Carbon::parse($this->date)->englishDayOfWeek);
    }

    public function dayLabel(): string
    {
        return WeeklyDay::label($this->weekdayKey());
    }

    public function timeRangeLabel(): string
    {
        if ($this->isWholeDay()) {
            return 'Sehari penuh';
        }

        return substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu',
        };
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }
}
