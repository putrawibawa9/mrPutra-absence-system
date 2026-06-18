<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // Copied from the owning payment for scoping + rule resolution.
            $table->string('division')->nullable();
            $table->string('format')->nullable();
            $table->string('learning_mode')->nullable();

            // available | consumed | forfeited | expired
            $table->string('status')->default('available');

            // What consumed/forfeited the token (single attendance or batch).
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_batch_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('expires_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->dateTime('forfeited_at')->nullable();
            $table->dateTime('expired_at')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['payment_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
