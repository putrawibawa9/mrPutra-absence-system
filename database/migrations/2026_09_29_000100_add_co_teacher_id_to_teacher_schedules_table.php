<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            // Co-teacher (guru pendamping/training) untuk jadwal mingguan ini.
            $table->foreignId('co_teacher_id')->nullable()->after('teacher_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('co_teacher_id');
        });
    }
};
