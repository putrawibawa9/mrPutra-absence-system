<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            // Pembeda untuk game dengan tema sama, mis. level/kelas ("Kids", "Advanced").
            $table->string('keterangan')->nullable()->after('tema');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn('keterangan');
        });
    }
};
