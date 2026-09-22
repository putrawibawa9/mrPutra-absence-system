<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            // Kategori sistem (mis. Fee Guru) tidak boleh dipilih untuk input manual.
            $table->boolean('is_system')->nullable()->default(false)->after('cost_behavior');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
