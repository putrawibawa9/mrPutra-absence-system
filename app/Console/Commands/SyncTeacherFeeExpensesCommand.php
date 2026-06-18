<?php

namespace App\Console\Commands;

use App\Services\AttendanceTeacherFeeService;
use Illuminate\Console\Command;

class SyncTeacherFeeExpensesCommand extends Command
{
    protected $signature = 'attendance:sync-teacher-fees {--user-id=1 : User ID to attribute created expense records to}';

    protected $description = 'Backfill and sync teacher fee expenses for existing attendances and attendance batches.';

    public function handle(AttendanceTeacherFeeService $teacherFeeService): int
    {
        $results = $teacherFeeService->backfill((int) $this->option('user-id'));

        $this->info('Teacher fee expenses synced successfully.');
        $this->line('Single attendances synced: '.$results['single_attendances']);
        $this->line('Batch attendances synced: '.$results['batch_attendances']);

        return self::SUCCESS;
    }
}
