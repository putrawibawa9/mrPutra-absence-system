<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jalan PALING AKHIR. Mengubah FK expenses.expense_category_id dari ON DELETE
 * CASCADE menjadi RESTRICT supaya menghapus kategori (yang masih dipakai) tidak
 * ikut menghapus data expense. Kategori tetap dinonaktifkan, bukan dihapus.
 *
 * SQLite (test) tidak mendukung mengubah FK lewat ALTER; dilewati di sana —
 * perilaku CASCADE/RESTRICT tidak diuji di level DB pada test.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->cascadeOnDelete();
        });
    }
};
