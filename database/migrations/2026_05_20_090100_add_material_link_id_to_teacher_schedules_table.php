<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            $table->foreignId('material_link_id')
                ->nullable()
                ->after('student_id')
                ->constrained('material_links')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_link_id');
        });
    }
};
