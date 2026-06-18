<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\TokenService;

class PaymentObserver
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    /**
     * Materialise the per-token ledger rows whenever a token paket is created.
     */
    public function created(Payment $payment): void
    {
        // Reload so database defaults (e.g. source_type) are reflected when the
        // caller created the row without setting every column explicitly.
        $this->tokenService->generateForPayment($payment->fresh() ?? $payment);
    }
}
