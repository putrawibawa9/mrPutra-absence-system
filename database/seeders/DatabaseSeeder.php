<?php

namespace Database\Seeders;

use App\Models\Package;
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
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'teacher@abspay.test'],
            [
                'name' => 'Teacher User',
                'role' => User::ROLE_TEACHER,
                'password' => Hash::make('password'),
            ]
        );

        foreach ([
            ['name' => '10 Sessions', 'total_sessions' => 10, 'price' => 500000],
            ['name' => '20 Sessions', 'total_sessions' => 20, 'price' => 900000],
        ] as $package) {
            Package::query()->updateOrCreate(
                ['name' => $package['name']],
                $package,
            );
        }
    }
}
