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
    'learning_module_id',
    'book_title',
    'receipt_number',
    'source_type',
    'division',
    'format',
    'learning_mode',
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

    public const SOURCE_TOKEN = 'token';
    public const SOURCE_BOOK = 'book';

    // Legacy source types kept only so historical records still render.
    public const SOURCE_PACKAGE = 'package';
    public const SOURCE_MANUAL = 'manual';

    /**
     * Source types that represent class token purchases (current + legacy).
     */
    public const TOKEN_SOURCES = [self::SOURCE_TOKEN, self::SOURCE_PACKAGE, self::SOURCE_MANUAL];

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

    public function learningModule(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('date');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * Whether this payment carries class tokens (vs. a book/module purchase).
     */
    public function isTokenSource(): bool
    {
        return in_array($this->source_type, self::TOKEN_SOURCES, true) && $this->total_sessions > 0;
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
            self::SOURCE_BOOK => $this->learningModule?->name ?: ($this->book_title ?: 'Pembayaran Buku'),
            default => 'Pembelian Token',
        };
    }

    public function isBook(): bool
    {
        return $this->source_type === self::SOURCE_BOOK;
    }

    public function pricePerSession(): int
    {
        if ($this->total_sessions <= 0) {
            return 0;
        }

        return intdiv($this->price_amount, $this->total_sessions);
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
