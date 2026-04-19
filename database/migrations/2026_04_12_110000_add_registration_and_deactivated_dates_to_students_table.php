<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('registration_date')->nullable()->after('email');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
        });

        DB::table('students')->update([
            'registration_date' => DB::raw('DATE(created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['registration_date', 'deactivated_at']);
        });
    }
};
