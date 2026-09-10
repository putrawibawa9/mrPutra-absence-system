<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
