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
    'book_title',
    'receipt_number',
    'source_type',
    'total_sessions',
    'remaining_sessions',
    'price_amount',
    'amount_paid',
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
    public const SOURCE_BOOK = 'book';

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_sessions' => 'integer',
            'remaining_sessions' => 'integer',
            'price_amount' => 'integer',
            'amount_paid' => 'integer',
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

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class)->latest('payment_date')->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->where('remaining_sessions', '>', 0);
    }

    public function displayLabel(): string
    {
        return match ($this->source_type) {
            self::SOURCE_BOOK => $this->book_title ?: 'Book / Module Payment',
            self::SOURCE_MANUAL => 'Manual Opening Balance',
            default => $this->package?->name ?? 'Package Payment',
        };
    }

    public function displayReceiptNumber(): string
    {
        return $this->receipt_number ?: 'KWT-'.optional($this->payment_date)->format('Ymd').'-'.$this->id;
    }

    public function outstandingAmount(): int
    {
        return max(0, $this->price_amount - $this->amount_paid);
    }

    public function isPartiallyPaid(): bool
    {
        return $this->price_amount > 0 && $this->amount_paid > 0 && $this->amount_paid < $this->price_amount;
    }

    public function isFullyPaid(): bool
    {
        return $this->price_amount === 0 || $this->amount_paid >= $this->price_amount;
    }

    public function signatureUrl(): ?string
    {
        if ($this->signature_path) {
            return Storage::disk('public')->url($this->signature_path);
        }

        return $this->signer?->signatureUrl();
    }
}
