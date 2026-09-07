<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table): void {
            $table->foreignId('classroom_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('classroom_id');
        });
    }
};
