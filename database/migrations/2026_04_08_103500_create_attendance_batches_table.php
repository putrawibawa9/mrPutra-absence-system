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
        Schema::create('attendance_batches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('attendance_batch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_batch_id');
        });

        Schema::dropIfExists('attendance_batches');
    }
};
