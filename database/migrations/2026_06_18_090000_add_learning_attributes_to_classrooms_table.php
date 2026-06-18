<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('learning_mode')->default('self_paced')->after('format'); // synchronous | self_paced
            $table->string('level')->nullable()->after('age_group');                // l1 | l2 | l3
        });

        // Backfill learning_mode from the division/format mapping:
        // English + Semi => synchronous (live cohort); everything else => self_paced.
        DB::table('classrooms')
            ->where('division', 'english')
            ->where('format', 'semi')
            ->update(['learning_mode' => 'synchronous']);
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['learning_mode', 'level']);
        });
    }
};
