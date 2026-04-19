<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('day_of_week', 16);
            $table->time('start_time');
            $table->time('end_time');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['teacher_id', 'day_of_week', 'is_active']);
            $table->index(['teacher_id', 'day_of_week', 'start_time', 'end_time'], 'teacher_schedule_time_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_schedules');
    }
};
