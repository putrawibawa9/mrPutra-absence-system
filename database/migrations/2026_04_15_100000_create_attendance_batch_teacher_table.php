<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_batch_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['attendance_batch_id', 'teacher_id']);
        });

        DB::table('attendance_batches')
            ->select(['id', 'teacher_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($batches): void {
                $rows = $batches
                    ->map(fn ($batch) => [
                        'attendance_batch_id' => $batch->id,
                        'teacher_id' => $batch->teacher_id,
                        'created_at' => $batch->created_at,
                        'updated_at' => $batch->updated_at,
                    ])
                    ->all();

                DB::table('attendance_batch_teacher')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_batch_teacher');
    }
};
