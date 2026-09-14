<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            // Perilaku biaya: 'fixed' (tetap, mis. sewa/Claude/partner) atau
            // 'variable' (mis. fee guru/fotokopi buku). null = belum ditandai.
            $table->string('cost_behavior')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('cost_behavior');
        });
    }
};
