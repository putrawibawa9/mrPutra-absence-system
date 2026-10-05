<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jadwal kelas boleh dibuat tanpa guru (mis. hasil import CSV), guru
        // di-assign belakangan lewat halaman Jadwal Guru.
        Schema::table('teacher_schedules', function (Blueprint $table): void {
            $table->foreignId('teacher_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table): void {
            $table->foreignId('teacher_id')->nullable(false)->change();
        });
    }
};
