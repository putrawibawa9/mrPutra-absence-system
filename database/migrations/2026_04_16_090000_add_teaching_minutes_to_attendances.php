<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance_batches', 'teaching_minutes')) {
            Schema::table('attendance_batches', function (Blueprint $table) {
                $table->unsignedInteger('teaching_minutes')->default(60)->after('date');
            });
        }

        if (! Schema::hasColumn('attendances', 'teaching_minutes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unsignedInteger('teaching_minutes')->default(60)->after('date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'teaching_minutes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('teaching_minutes');
            });
        }

        if (Schema::hasColumn('attendance_batches', 'teaching_minutes')) {
            Schema::table('attendance_batches', function (Blueprint $table) {
                $table->dropColumn('teaching_minutes');
            });
        }
    }
};
