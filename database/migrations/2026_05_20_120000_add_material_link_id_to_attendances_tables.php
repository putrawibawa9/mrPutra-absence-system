<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('material_link_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('material_links')
                ->nullOnDelete();
        });

        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->foreignId('material_link_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained('material_links')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_link_id');
        });

        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_link_id');
        });
    }
};
