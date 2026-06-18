<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\TokenService;
use Illuminate\Console\Command;

class BackfillTokensCommand extends Command
{
    protected $signature = 'tokens:backfill';

    protected $description = 'Create token ledger rows for existing token payments that have none yet.';

    public function handle(TokenService $tokenService): int
    {
        $payments = 0;
        $tokens = 0;

        Payment::query()
            ->whereIn('source_type', Payment::TOKEN_SOURCES)
            ->where('total_sessions', '>', 0)
            ->doesntHave('tokens')
            ->chunkById(100, function ($chunk) use ($tokenService, &$payments, &$tokens): void {
                foreach ($chunk as $payment) {
                    $created = $tokenService->generateForPayment($payment);

                    if ($created > 0) {
                        $payments++;
                        $tokens += $created;
                    }
                }
            });

        $this->info("Backfilled {$tokens} token(s) across {$payments} payment(s).");

        return self::SUCCESS;
    }
}
