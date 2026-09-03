<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['name', 'phone', 'email', 'program_type', 'book_info', 'registration_date', 'is_active', 'deactivated_at'])]
class Student extends Model
{
    use HasFactory;

    public const PROGRAM_CODING = 'coding';
    public const PROGRAM_ENGLISH = 'english';

    /** Sisa token <= angka ini dianggap menipis & dimunculkan di alert admin. */
    public const LOW_SESSION_THRESHOLD = 1;

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

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student')
            ->withTimestamps()
            ->orderBy('name');
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

    public function latestHomeworkAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)
            ->ofMany(['date' => 'max', 'id' => 'max'], function ($query) {
                $query->whereNotNull('homework_content')
                    ->where('homework_content', '!=', '');
            });
    }

    public function latestSessionPayment(): HasOne
    {
        return $this->hasOne(Payment::class)
            ->ofMany(['payment_date' => 'max', 'id' => 'max'], function ($query) {
                $query->where('total_sessions', '>', 0)
                    ->where('price_amount', '>', 0);
            });
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

    /**
     * Saldo token bersih: sisa token dikurangi utang token (absensi tanpa token).
     * Positif = tersedia, 0 = kosong, negatif = utang (mis. -2).
     */
    public function getNetTokenBalance(): int
    {
        return $this->getRemainingSessions() - $this->getTokenDebtCount();
    }

    /**
     * Label saldo token bersih sebagai angka bertanda, mis. "-2 token" / "0 token" / "5 token".
     * Terima nilai net yang sudah dihitung (untuk list) agar hemat query.
     */
    public static function tokenBalanceLabel(int $net): string
    {
        return $net.' token';
    }

    public function netTokenLabel(): string
    {
        return static::tokenBalanceLabel($this->getNetTokenBalance());
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

    public function buildLowSessionReminderMessage(?int $remainingSessions = null): string
    {
        $remainingSessions ??= $this->getRemainingSessions();
        $remainingLabel = $remainingSessions <= 0
            ? 'saat ini sudah habis'
            : 'saat ini tinggal '.($remainingSessions === 1 ? '1 kali pertemuan' : $remainingSessions.' kali pertemuan');

        $continuationMessage = 'Apabila berkenan melanjutkan pertemuan berikutnya, kami siap membantu untuk penjadwalan dan informasi biaya les selanjutnya.';

        if ($this->latestSessionPayment && $this->latestSessionPayment->price_amount > 0 && $this->latestSessionPayment->total_sessions > 0) {
            $continuationMessage = 'Apabila berkenan melanjutkan untuk '
                .$this->latestSessionPayment->total_sessions
                .' kali pertemuan berikutnya, kami mohon kesediaannya untuk menyiapkan biaya les sebesar Rp '
                .number_format($this->latestSessionPayment->price_amount, 0, ',', '.')
                .'.';
        }

        return "Halo Bapak/Ibu / Ananda, semoga sehat selalu.\n\n"
            ."Kami ingin menyampaikan bahwa sisa pertemuan untuk Ananda {$this->name} {$remainingLabel}.\n\n"
            .$continuationMessage."\n\n"
            ."Terima kasih banyak.";
    }

    public function lowSessionReminderWhatsAppUrl(?int $remainingSessions = null): ?string
    {
        $whatsAppNumber = $this->whatsappNumber();

        if (! $whatsAppNumber) {
            return null;
        }

        return 'https://wa.me/'.$whatsAppNumber.'?text='.rawurlencode(
            $this->buildLowSessionReminderMessage($remainingSessions)
        );
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
