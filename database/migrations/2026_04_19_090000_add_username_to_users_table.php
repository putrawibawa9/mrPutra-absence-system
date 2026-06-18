<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('name');
            });
        }

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                if (! blank($user->username)) {
                    return;
                }

                $seed = $user->name ?: Str::before((string) $user->email, '@');
                $base = Str::lower(preg_replace('/[^a-z0-9._-]+/', '', Str::slug($seed, '_')));
                $base = $base ?: 'user';
                $username = $base;
                $counter = 1;

                while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $base.$counter;
                    $counter++;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'username' => $username,
                ]);
            });

        $indexes = collect(Schema::getIndexes('users'))->pluck('name')->all();

        if (! in_array('users_username_unique', $indexes, true)) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('users'))->pluck('name')->all();

        Schema::table('users', function (Blueprint $table) use ($indexes) {
            if (in_array('users_username_unique', $indexes, true)) {
                $table->dropUnique('users_username_unique');
            }

            $table->dropColumn('username');
        });
    }
};
