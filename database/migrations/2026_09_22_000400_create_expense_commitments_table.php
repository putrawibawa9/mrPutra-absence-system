<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_item_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('total_amount');
            $table->unsignedSmallInteger('months');
            $table->date('start_date');
            $table->char('amortization_group', 36)->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_commitments');
    }
};
