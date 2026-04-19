<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup-mysql')
    ->dailyAt('02:00')
    ->timezone(config('app.backup_timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/mysql-backup.log'));
