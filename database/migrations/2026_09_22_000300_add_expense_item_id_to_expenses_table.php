<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_item_id')->nullable()->after('expense_category_id');
            $table->index('expense_item_id');
        });

        // FK RESTRICT: item yang sudah dipakai expense tidak bisa dihapus.
        // SQLite (test) tidak mendukung ALTER ADD FOREIGN KEY; dilewati di sana.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('expenses', function (Blueprint $table) {
                $table->foreign('expense_item_id')->references('id')->on('expense_items')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['expense_item_id']);
            });
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['expense_item_id']);
            $table->dropColumn('expense_item_id');
        });
    }
};
