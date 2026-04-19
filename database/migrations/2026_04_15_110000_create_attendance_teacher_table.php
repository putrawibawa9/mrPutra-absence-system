<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['attendance_id', 'teacher_id']);
        });

        DB::table('attendances')
            ->select(['id', 'teacher_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($attendances): void {
                $rows = $attendances
                    ->map(fn ($attendance) => [
                        'attendance_id' => $attendance->id,
                        'teacher_id' => $attendance->teacher_id,
                        'created_at' => $attendance->created_at,
                        'updated_at' => $attendance->updated_at,
                    ])
                    ->all();

                DB::table('attendance_teacher')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_teacher');
    }
};
