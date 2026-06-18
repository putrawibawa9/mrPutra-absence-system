<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_material_link')) {
            Schema::create('attendance_material_link', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_link_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['attendance_id', 'material_link_id'], 'attendance_material_link_unique');
            });
        }

        if (! Schema::hasTable('attendance_batch_material_link')) {
            Schema::create('attendance_batch_material_link', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_batch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_link_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['attendance_batch_id', 'material_link_id'], 'attendance_batch_material_link_unique');
            });
        }

        if (Schema::hasTable('attendance_material_link')) {
            DB::table('attendances')
                ->whereNotNull('material_link_id')
                ->orderBy('id')
                ->chunkById(100, function ($attendances): void {
                    $rows = [];
                    $now = now();

                    foreach ($attendances as $attendance) {
                        $rows[] = [
                            'attendance_id' => $attendance->id,
                            'material_link_id' => $attendance->material_link_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('attendance_material_link')->insertOrIgnore($rows);
                    }
                });
        }

        if (Schema::hasTable('attendance_batch_material_link')) {
            DB::table('attendance_batches')
                ->whereNotNull('material_link_id')
                ->orderBy('id')
                ->chunkById(100, function ($batches): void {
                    $rows = [];
                    $now = now();

                    foreach ($batches as $batch) {
                        $rows[] = [
                            'attendance_batch_id' => $batch->id,
                            'material_link_id' => $batch->material_link_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('attendance_batch_material_link')->insertOrIgnore($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_batch_material_link');
        Schema::dropIfExists('attendance_material_link');
    }
};
