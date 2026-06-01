<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('teacher_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->after('teacher_user_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('attendance_batch_id')->nullable()->after('attendance_id')->constrained('attendance_batches')->cascadeOnDelete();

            $table->unique(['attendance_id', 'teacher_user_id'], 'expenses_attendance_teacher_unique');
            $table->unique(['attendance_batch_id', 'teacher_user_id'], 'expenses_batch_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_attendance_teacher_unique');
            $table->dropUnique('expenses_batch_teacher_unique');
            $table->dropConstrainedForeignId('attendance_batch_id');
            $table->dropConstrainedForeignId('attendance_id');
            $table->dropConstrainedForeignId('teacher_user_id');
        });
    }
};
