<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['name', 'phone', 'email', 'program_type', 'book_info', 'registration_date', 'is_active', 'deactivated_at'])]
class Student extends Model
{
    use HasFactory;

    public const PROGRAM_CODING = 'coding';
    public const PROGRAM_ENGLISH = 'english';

    /** Sisa token <= angka ini dianggap menipis & dimunculkan di alert admin. */
    public const LOW_SESSION_THRESHOLD = 1;

    /** Absen berturut-turut >= angka ini => murid perlu ditanyakan kabarnya. */
    public const CONSECUTIVE_ABSENCE_ALERT = 3;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'registration_date' => 'date',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Begitu murid di-nonaktifkan, otomatis keluarkan dari semua kelas
        // (kick dari grup) supaya tidak lagi muncul & tidak bisa diabsen.
        static::updated(function (Student $student): void {
            if ($student->wasChanged('is_active') && ! $student->is_active) {
                $student->classrooms()->detach();
            }
        });
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

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class)->latest();
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
        return $net.' pertemuan';
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

        $jumlah = '';
        $biaya = '';
        if ($this->latestSessionPayment && $this->latestSessionPayment->price_amount > 0 && $this->latestSessionPayment->total_sessions > 0) {
            $jumlah = (string) $this->latestSessionPayment->total_sessions;
            $biaya = number_format($this->latestSessionPayment->price_amount, 0, ',', '.');
        }

        return MessageTemplate::render('billing', [
            'nama' => $this->name,
            'kalimat_sisa' => $remainingLabel,
            'kalimat_lanjut' => $continuationMessage,
            'sisa_pertemuan' => (string) max(0, $remainingSessions),
            'jumlah_pertemuan' => $jumlah,
            'biaya' => $biaya,
            'info_rekening' => MessageTemplate::REKENING_BLOCK,
            'link_bayar' => $this->caraBayarUrl(),
        ]);
    }

    /** Halaman publik "Cara Bayar" (QRIS + rekening + nominal), diamankan signed URL. */
    public function caraBayarUrl(): string
    {
        return URL::signedRoute('payment-info.show', ['student' => $this]);
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

    public function buildAttendanceCheckInMessage(): string
    {
        return MessageTemplate::render('inactive_followup', ['nama' => $this->name]);
    }

    public function attendanceCheckInWhatsAppUrl(): ?string
    {
        $whatsAppNumber = $this->whatsappNumber();

        if (! $whatsAppNumber) {
            return null;
        }

        return 'https://wa.me/'.$whatsAppNumber.'?text='.rawurlencode(
            $this->buildAttendanceCheckInMessage()
        );
    }

    public function feedbackFormUrl(): string
    {
        return URL::temporarySignedRoute('feedback.create', now()->addDays(30), ['student' => $this->id]);
    }

    public function buildFeedbackRequestMessage(): string
    {
        return MessageTemplate::render('feedback', [
            'nama' => $this->name,
            'link_feedback' => $this->feedbackFormUrl(),
        ]);
    }

    public function buildCompletedMessage(): string
    {
        return MessageTemplate::render('completed', [
            'nama' => $this->name,
            'link_feedback' => $this->feedbackFormUrl(),
        ]);
    }

    public function completedWhatsAppUrl(): ?string
    {
        $whatsAppNumber = $this->whatsappNumber();

        if (! $whatsAppNumber) {
            return null;
        }

        return 'https://wa.me/'.$whatsAppNumber.'?text='.rawurlencode($this->buildCompletedMessage());
    }

    public function feedbackRequestWhatsAppUrl(): ?string
    {
        $whatsAppNumber = $this->whatsappNumber();

        if (! $whatsAppNumber) {
            return null;
        }

        return 'https://wa.me/'.$whatsAppNumber.'?text='.rawurlencode(
            $this->buildFeedbackRequestMessage()
        );
    }

    public static function programOptions(): array
    {
        return [
            self::PROGRAM_CODING => 'Coding',
            self::PROGRAM_ENGLISH => 'English',
        ];
    }

    /**
     * Program murid diturunkan dari divisi kelas yang dia ikuti (bukan lagi
     * kolom program_type). Satu murid bisa ikut >1 divisi, mis. "English & Coding".
     */
    public function programLabel(): string
    {
        $divisions = ($this->relationLoaded('classrooms') ? $this->classrooms : $this->classrooms()->get())
            ->pluck('division')
            ->filter()
            ->unique();

        if ($divisions->isEmpty()) {
            return 'Belum ada kelas';
        }

        return $divisions
            ->map(fn ($division) => Classroom::divisionOptions()[$division] ?? ucfirst((string) $division))
            ->sort()
            ->join(' & ');
    }
}
