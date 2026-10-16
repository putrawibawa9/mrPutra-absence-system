<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist sesi: game yang dipakai (opsional) + apakah materi diberikan.
        Schema::table('attendance_batches', function (Blueprint $table): void {
            $table->foreignId('game_id')->nullable()->after('material_link_id')->constrained()->nullOnDelete();
            $table->boolean('materi_done')->default(false)->after('game_id');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignId('game_id')->nullable()->after('material_link_id')->constrained()->nullOnDelete();
            $table->boolean('materi_done')->default(false)->after('game_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_id');
            $table->dropColumn('materi_done');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_id');
            $table->dropColumn('materi_done');
        });
    }
};
