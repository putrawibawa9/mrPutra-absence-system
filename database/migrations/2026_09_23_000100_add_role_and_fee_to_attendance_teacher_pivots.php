<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Co-teacher support: tiap guru pada satu pertemuan bisa punya peran (teacher /
 * co_teacher) dan fee custom. fee_amount NULL = pakai fee standar; role NULL =
 * dianggap 'teacher'. Additive & nullable — data lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_teacher', function (Blueprint $table) {
            $table->string('role')->nullable()->after('teacher_id');
            $table->unsignedInteger('fee_amount')->nullable()->after('role');
        });

        Schema::table('attendance_batch_teacher', function (Blueprint $table) {
            $table->string('role')->nullable()->after('teacher_id');
            $table->unsignedInteger('fee_amount')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_teacher', function (Blueprint $table) {
            $table->dropColumn(['role', 'fee_amount']);
        });

        Schema::table('attendance_batch_teacher', function (Blueprint $table) {
            $table->dropColumn(['role', 'fee_amount']);
        });
    }
};
