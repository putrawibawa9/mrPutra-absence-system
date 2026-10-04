<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            // Relasi ke master buku. Nullable: kelas boleh belum pilih buku.
            // Kolom lama book_title dibiarkan (data-preserving); app memakai book_id.
            $table->foreignId('book_id')->nullable()->after('book_title')->constrained('books')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('book_id');
        });
    }
};
