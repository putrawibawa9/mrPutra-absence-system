<?php

namespace App\Console\Commands;

use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireTokensCommand extends Command
{
    protected $signature = 'tokens:expire';

    protected $description = 'Flag available tokens past their expiry window as expired and sync remaining sessions.';

    public function handle(TokenService $tokenService): int
    {
        $expired = DB::transaction(fn () => $tokenService->expirePastDue());

        $this->info("Expired {$expired} token(s).");

        return self::SUCCESS;
    }
}
