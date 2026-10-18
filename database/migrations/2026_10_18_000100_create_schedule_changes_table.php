<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengecualian per-tanggal di atas template jadwal mingguan (TeacherSchedule):
        // guru pengganti, pindah sesi ke tanggal lain, atau pembatalan — hanya untuk
        // 1 tanggal tertentu. Template mingguan tetap jadi sumber jadwal rutin.
        Schema::create('schedule_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('date'); // tanggal asli occurrence yang diubah
            $table->foreignId('substitute_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('moved_to_date')->nullable();
            $table->time('moved_start_time')->nullable();
            $table->time('moved_end_time')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->string('reason')->nullable();
            $table->foreignId('teacher_leave_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['teacher_schedule_id', 'date']);
            $table->index('moved_to_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_changes');
    }
};
