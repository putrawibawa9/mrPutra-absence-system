<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->text('learning_journal')->nullable()->after('notes');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->text('learning_journal')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->dropColumn('learning_journal');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('learning_journal');
        });
    }
};
