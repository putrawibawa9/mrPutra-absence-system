<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'student_id',
    'package_id',
    'receipt_number',
    'source_type',
    'total_sessions',
    'remaining_sessions',
    'payment_date',
    'notes',
    'signed_by_user_id',
    'signature_path',
])]
class Payment extends Model
{
    use HasFactory;

    public const SOURCE_PACKAGE = 'package';
    public const SOURCE_MANUAL = 'manual';

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_sessions' => 'integer',
            'remaining_sessions' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('date');
    }

    public function scopeActive($query)
    {
        return $query->where('remaining_sessions', '>', 0);
    }

    public function displayLabel(): string
    {
        return $this->package?->name ?? 'Manual Opening Balance';
    }

    public function displayReceiptNumber(): string
    {
        return $this->receipt_number ?: 'KWT-'.optional($this->payment_date)->format('Ymd').'-'.$this->id;
    }

    public function signatureUrl(): ?string
    {
        if (! $this->signature_path) {
            return null;
        }

        return Storage::disk('public')->url($this->signature_path);
    }
}
