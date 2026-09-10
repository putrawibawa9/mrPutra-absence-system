<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use Illuminate\Console\Command;

class BackfillClassroomCodesCommand extends Command
{
    protected $signature = 'classrooms:backfill-codes';

    protected $description = 'Assign an auto code (e.g. ESA-01) to classrooms that do not have one yet.';

    public function handle(): int
    {
        $classrooms = Classroom::query()->whereNull('code')->orderBy('id')->get();
        $count = 0;

        foreach ($classrooms as $classroom) {
            $classroom->code = Classroom::nextCode($classroom->division, $classroom->format, $classroom->age_group);
            $classroom->save();
            $count++;
        }

        $this->info("Assigned codes to {$count} classroom(s).");

        return self::SUCCESS;
    }
}
