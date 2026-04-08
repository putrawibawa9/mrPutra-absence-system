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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->unique()->after('id');
            $table->foreignId('signed_by_user_id')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->string('signature_path')->nullable()->after('signed_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by_user_id');
            $table->dropColumn(['receipt_number', 'signature_path']);
        });
    }
};
