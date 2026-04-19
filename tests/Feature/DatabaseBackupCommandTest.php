<?php

namespace Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    public function test_mysql_backup_command_runs_mysqldump_into_database_folder(): void
    {
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'root',
            'password' => 'secret',
        ]);

        Process::fake(function (PendingProcess $process) {
            file_put_contents(database_path('test-backup.sql'), '-- fake backup');

            return Process::result(
                output: '',
                errorOutput: '',
                exitCode: 0,
            );
        });

        $this->artisan('db:backup-mysql', [
            '--filename' => 'test-backup.sql',
        ])->assertSuccessful();

        $this->assertFileExists(database_path('test-backup.sql'));

        @unlink(database_path('test-backup.sql'));
    }
}
