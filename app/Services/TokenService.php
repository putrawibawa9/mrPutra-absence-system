<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Token;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for token lifecycle: generation, consumption,
 * forfeiting, release and expiry. The legacy `payments.remaining_sessions`
 * counter is kept in lockstep with the number of `available` tokens so existing
 * reads (dashboards, reminders, settleTokenDebt) keep working unchanged.
 */
class TokenService
{
    /**
     * Create the ledger rows for a token paket. Idempotent: does nothing if the
     * payment is not a token source or already has tokens. Used both at payment
     * creation (observer) and by the backfill command for historical rows.
     */
    public function generateForPayment(Payment $payment): int
    {
        if (! $payment->isTokenSource()) {
            return 0;
        }

        if ($payment->tokens()->exists()) {
            return 0;
        }

        $available = max(0, (int) $payment->remaining_sessions);
        $consumed = max(0, (int) $payment->total_sessions - $available);

        $rows = [];
        $now = now();

        for ($i = 0; $i < $available; $i++) {
            $rows[] = $this->row($payment, Token::STATUS_AVAILABLE, $now);
        }

        // Backfill historical consumption (total - remaining) as consumed tokens.
        for ($i = 0; $i < $consumed; $i++) {
            $rows[] = $this->row($payment, Token::STATUS_CONSUMED, $now, ['consumed_at' => $payment->payment_date ?? $now]);
        }

        if ($rows !== []) {
            Token::query()->insert($rows);
        }

        return count($rows);
    }

    /**
     * Consume one available token from a specific payment for a present student.
     * Returns null when no token is available (caller treats it as token debt).
     */
    public function consume(Payment $payment, Attendance $attendance, ?CarbonInterface $sessionDate = null): ?Token
    {
        $token = $this->lockAvailableToken($payment);

        if (! $token) {
            return null;
        }

        $this->applyExpiryWindow($payment, $sessionDate ?? $attendance->date);

        $token->forceFill([
            'status' => Token::STATUS_CONSUMED,
            'attendance_id' => $attendance->id,
            'attendance_batch_id' => $attendance->attendance_batch_id,
            'consumed_at' => now(),
        ])->save();

        $payment->decrement('remaining_sessions');

        return $token;
    }

    /**
     * Forfeit one available token for a student who was absent from a
     * synchronous (live cohort) session that took place.
     */
    public function forfeit(Payment $payment, ?AttendanceBatch $batch = null, ?CarbonInterface $sessionDate = null): ?Token
    {
        $token = $this->lockAvailableToken($payment);

        if (! $token) {
            return null;
        }

        if ($sessionDate) {
            $this->applyExpiryWindow($payment, $sessionDate);
        }

        $token->forceFill([
            'status' => Token::STATUS_FORFEITED,
            'attendance_batch_id' => $batch?->id,
            'forfeited_at' => now(),
        ])->save();

        $payment->decrement('remaining_sessions');

        return $token;
    }

    /**
     * Return one consumed token of a payment back to the available pool when an
     * attendance is edited away from it or deleted. Prefers the token linked to
     * the given attendance, but falls back to any consumed token of the payment
     * so historical/backfilled rows (consumed but unlinked) rebalance correctly.
     */
    public function release(Payment $payment, ?Attendance $attendance = null): ?Token
    {
        $token = null;

        if ($attendance) {
            $token = $payment->tokens()
                ->where('status', Token::STATUS_CONSUMED)
                ->where('attendance_id', $attendance->id)
                ->lockForUpdate()
                ->first();
        }

        $token ??= $payment->tokens()
            ->where('status', Token::STATUS_CONSUMED)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (! $token) {
            return null;
        }

        $token->forceFill([
            'status' => Token::STATUS_AVAILABLE,
            'attendance_id' => null,
            'attendance_batch_id' => null,
            'consumed_at' => null,
        ])->save();

        $payment->increment('remaining_sessions');

        return $token;
    }

    /**
     * Flag available tokens whose expiry window has passed as expired and keep
     * the remaining_sessions counter in sync. Returns the number expired.
     */
    public function expirePastDue(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();

        $tokens = Token::query()
            ->where('status', Token::STATUS_AVAILABLE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $asOf)
            ->lockForUpdate()
            ->get();

        foreach ($tokens as $token) {
            $token->forceFill([
                'status' => Token::STATUS_EXPIRED,
                'expired_at' => now(),
            ])->save();

            $token->payment?->decrement('remaining_sessions');
        }

        return $tokens->count();
    }

    /**
     * Set the expiry window the first time a paket is used. Counted from the
     * first session date so students who delay starting keep their full window.
     */
    protected function applyExpiryWindow(Payment $payment, CarbonInterface|string|null $sessionDate): void
    {
        if ($payment->tokens()->whereNotNull('expires_at')->exists()) {
            return; // window already established for this paket
        }

        $expiresAt = Carbon::parse($sessionDate ?? now())
            ->addDays((int) config('tokens.validity_period_days', 90))
            ->endOfDay();

        $payment->tokens()->whereNull('expires_at')->update(['expires_at' => $expiresAt]);
    }

    protected function lockAvailableToken(Payment $payment): ?Token
    {
        return $payment->tokens()
            ->where('status', Token::STATUS_AVAILABLE)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    protected function row(Payment $payment, string $status, CarbonInterface $now, array $extra = []): array
    {
        return array_merge([
            'payment_id' => $payment->id,
            'student_id' => $payment->student_id,
            'division' => $payment->division,
            'format' => $payment->format,
            'learning_mode' => $payment->learning_mode,
            'status' => $status,
            'attendance_id' => null,
            'attendance_batch_id' => null,
            'expires_at' => null,
            'consumed_at' => null,
            'forfeited_at' => null,
            'expired_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $extra);
    }
}
