<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupMysqlDatabaseCommand extends Command
{
    protected $signature = 'db:backup-mysql {--filename= : Custom backup filename inside the database folder}';

    protected $description = 'Create a MySQL backup inside the root database folder';

    public function handle(): int
    {
        $connection = config('database.default');
        $databaseConfig = config("database.connections.{$connection}");

        if (($databaseConfig['driver'] ?? null) !== 'mysql') {
            $this->error('This backup command only supports MySQL connections.');

            return self::FAILURE;
        }

        $backupFilename = $this->option('filename') ?: 'mysql-backup-'.now()->format('Y-m-d-His').'.sql';
        $backupPath = database_path($backupFilename);
        $backupDirectory = dirname($backupPath);

        if (! is_dir($backupDirectory)) {
            mkdir($backupDirectory, 0755, true);
        }

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --skip-lock-tables %s > %s',
            escapeshellarg((string) ($databaseConfig['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($databaseConfig['port'] ?? '3306')),
            escapeshellarg((string) ($databaseConfig['username'] ?? 'root')),
            escapeshellarg((string) ($databaseConfig['database'] ?? '')),
            escapeshellarg($backupPath),
        );

        $process = Process::path(base_path())
            ->env(array_filter([
                'MYSQL_PWD' => $databaseConfig['password'] ?? null,
            ]))
            ->timeout(600)
            ->run($command);

        if ($process->failed()) {
            $this->error('MySQL backup failed.');
            $this->error($process->errorOutput() ?: $process->output());

            return self::FAILURE;
        }

        $this->info('MySQL backup created: '.$backupPath);

        return self::SUCCESS;
    }
}
