<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Template pesan WhatsApp yang bisa diedit admin. Body kosong = pakai
        // default bawaan (App\Models\MessageTemplate::DEFAULTS).
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique(); // billing | inactive_followup | feedback | completed
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
