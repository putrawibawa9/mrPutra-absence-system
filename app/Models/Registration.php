<?php

namespace App\Models;

use App\Support\WeeklyDay;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_name',
    'guardian_name',
    'phone',
    'email',
    'age',
    'program',
    'format_preference',
    'goal',
    'level_note',
    'available_days',
    'time_preferences',
    'referral_source',
    'notes',
    'status',
    'student_id',
    'processed_by_user_id',
    'processed_at',
])]
class Registration extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'available_days' => 'array',
            'time_preferences' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public static function programOptions(): array
    {
        return [
            'english' => 'English',
            'coding' => 'Coding',
            'both' => 'English & Coding',
        ];
    }

    public static function formatOptions(): array
    {
        return [
            'private' => 'Private (1-on-1)',
            'semi' => 'Grup',
        ];
    }

    /** Pilihan tujuan les (dropdown) supaya jawabannya terarah. */
    public static function goalOptions(): array
    {
        return [
            'conversation' => 'Percakapan sehari-hari',
            'exam' => 'Persiapan ujian (IELTS/TOEFL/sekolah)',
            'academic' => 'Akademik / nilai sekolah',
            'career' => 'Karier / dunia kerja',
            'basic' => 'Dasar / grammar',
            'kids' => 'English untuk anak',
            'other' => 'Lainnya',
        ];
    }

    public function goalLabel(): string
    {
        return self::goalOptions()[$this->goal] ?? ($this->goal ?: '-');
    }

    public static function timeOptions(): array
    {
        return [
            'pagi' => 'Pagi',
            'siang' => 'Siang',
            'sore' => 'Sore',
            'malam' => 'Malam',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function programLabel(): string
    {
        return self::programOptions()[$this->program] ?? $this->program;
    }

    public function formatLabel(): string
    {
        return self::formatOptions()[$this->format_preference] ?? '-';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'Diterima',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu',
        };
    }

    public function availableDayLabels(): string
    {
        return collect($this->available_days ?? [])
            ->map(fn ($day) => WeeklyDay::label($day))
            ->join(', ') ?: '-';
    }

    public function timePreferenceLabels(): string
    {
        $options = self::timeOptions();

        return collect($this->time_preferences ?? [])
            ->map(fn ($slot) => $options[$slot] ?? $slot)
            ->join(', ') ?: '-';
    }
}
