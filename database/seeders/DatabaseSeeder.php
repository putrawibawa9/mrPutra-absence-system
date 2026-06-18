<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@abspay.test'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'teacher@abspay.test'],
            [
                'name' => 'Teacher User',
                'username' => 'teacher',
                'role' => User::ROLE_TEACHER,
                'password' => Hash::make('password'),
            ]
        );
    }
}
