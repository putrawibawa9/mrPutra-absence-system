<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            // Judul buku yang dipakai kelas, agar guru lain tahu. Boleh kosong
            // saat kelas dibuat, diisi/diedit kemudian lewat detail kelas.
            $table->string('book_title')->nullable()->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropColumn('book_title');
        });
    }
};
