<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            // RESTRICT: item mengunci kategori; kategori yang masih dipakai item
            // tidak bisa terhapus (sejalan dengan larangan menghapus kategori).
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('name')->unique();
            $table->text('keywords')->nullable();
            $table->unsignedInteger('default_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
