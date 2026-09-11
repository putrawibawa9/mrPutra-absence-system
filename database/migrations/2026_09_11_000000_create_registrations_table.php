<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('student_name');
            $table->string('guardian_name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('program');                       // english | coding | both
            $table->string('format_preference')->nullable(); // private | semi | unsure
            $table->text('goal')->nullable();
            $table->string('level_note')->nullable();
            $table->json('available_days')->nullable();       // ["monday", ...]
            $table->json('time_preferences')->nullable();     // ["pagi", ...]
            $table->string('referral_source')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');     // pending | accepted | rejected
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
