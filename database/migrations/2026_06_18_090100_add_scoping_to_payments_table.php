<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Token scoping: a token paket belongs to a division+format so an
            // English Private token can never be spent on a Coding class.
            $table->string('division')->nullable()->after('source_type');       // english | coding
            $table->string('format')->nullable()->after('division');            // private | semi
            $table->string('learning_mode')->nullable()->after('format');       // synchronous | self_paced
        });

        // Backfill division from the owning student's program_type. format and
        // learning_mode stay null for legacy rows: null acts as a wildcard so
        // historical consumption keeps working against any class.
        DB::table('payments')
            ->whereNull('division')
            ->update([
                'division' => DB::raw('(select program_type from students where students.id = payments.student_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['division', 'format', 'learning_mode']);
        });
    }
};
