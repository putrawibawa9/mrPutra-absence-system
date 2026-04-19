<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['name', 'phone', 'email', 'program_type', 'book_info', 'registration_date', 'is_active', 'deactivated_at'])]
class Student extends Model
{
    use HasFactory;

    public const PROGRAM_CODING = 'coding';
    public const PROGRAM_ENGLISH = 'english';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'registration_date' => 'date',
            'deactivated_at' => 'datetime',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('date');
    }

    public function latestActivePayment(): HasOne
    {
        return $this->hasOne(Payment::class)
            ->ofMany(['payment_date' => 'max', 'id' => 'max'], function ($query) {
                $query->where('remaining_sessions', '>', 0);
            });
    }

    public function latestAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)
            ->ofMany(['date' => 'max', 'id' => 'max']);
    }

    public function getRemainingSessions(): int
    {
        return (int) $this->payments()->sum('remaining_sessions');
    }

    public function getTokenDebtCount(): int
    {
        return $this->attendances()
            ->whereNull('payment_id')
            ->count();
    }

    public function getTokenDebtLabel(): string
    {
        $debtCount = $this->getTokenDebtCount();

        return $debtCount.' session'.($debtCount === 1 ? '' : 's');
    }

    public function getOutstandingPaymentDebt(): int
    {
        return (int) $this->payments()->get()->sum(fn (Payment $payment) => $payment->outstandingAmount());
    }

    public function getOutstandingPaymentDebtLabel(): string
    {
        return 'Rp '.number_format($this->getOutstandingPaymentDebt(), 0, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function formattedRegistrationDate(): string
    {
        return $this->registration_date?->format('d M Y') ?? '-';
    }

    public function formattedDeactivatedAt(): string
    {
        return $this->deactivated_at?->format('d M Y H:i') ?? '-';
    }

    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if (blank($digits)) {
            return null;
        }

        if (Str::startsWith($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (Str::startsWith($digits, '62')) {
            return $digits;
        }

        return $digits;
    }

    public static function programOptions(): array
    {
        return [
            self::PROGRAM_CODING => 'Coding',
            self::PROGRAM_ENGLISH => 'English',
        ];
    }

    public function programLabel(): string
    {
        return static::programOptions()[$this->program_type] ?? 'Not Set';
    }
}
