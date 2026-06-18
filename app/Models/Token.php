<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'student_id',
    'division',
    'format',
    'learning_mode',
    'status',
    'attendance_id',
    'attendance_batch_id',
    'expires_at',
    'consumed_at',
    'forfeited_at',
    'expired_at',
])]
class Token extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_FORFEITED = 'forfeited';
    public const STATUS_EXPIRED = 'expired';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'forfeited_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AttendanceBatch::class, 'attendance_batch_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Tokens that are not past their expiry window (null expires_at = never expires yet).
     */
    public function scopeNotExpired($query, ?\DateTimeInterface $asOf = null)
    {
        $asOf ??= now();

        return $query->where(function ($query) use ($asOf) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>=', $asOf);
        });
    }

    /**
     * Match the token scope against a class context. A null attribute on the
     * token acts as a wildcard (legacy tokens match any class).
     */
    public function scopeForContext($query, ?string $division, ?string $format)
    {
        return $query
            ->when($division, fn ($query, $division) => $query->where(function ($query) use ($division) {
                $query->whereNull('division')->orWhere('division', $division);
            }))
            ->when($format, fn ($query, $format) => $query->where(function ($query) use ($format) {
                $query->whereNull('format')->orWhere('format', $format);
            }));
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
