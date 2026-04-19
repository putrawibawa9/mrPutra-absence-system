<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_new_completed_and_active_student_metrics(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        Student::query()->create([
            'name' => 'New This Month',
            'phone' => '0811111101',
            'email' => 'new-this-month@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => Carbon::now()->subDays(2)->toDateString(),
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Student::query()->create([
            'name' => 'Old Student',
            'phone' => '0811111102',
            'email' => 'old-student@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => Carbon::now()->subMonth()->addDay()->toDateString(),
            'is_active' => true,
            'created_at' => Carbon::now()->subMonth()->addDay(),
        ]);

        Student::query()->create([
            'name' => 'Exited This Month',
            'phone' => '0811111103',
            'email' => 'exited-this-month@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => Carbon::now()->subMonth()->toDateString(),
            'is_active' => false,
            'deactivated_at' => Carbon::now()->subDays(1),
            'created_at' => Carbon::now()->subMonth(),
        ]);

        Student::query()->create([
            'name' => 'Inactive Student',
            'phone' => '0811111104',
            'email' => 'inactive-student@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => Carbon::now()->subDays(3)->toDateString(),
            'is_active' => false,
            'deactivated_at' => Carbon::now()->subMonth(),
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Pendaftaran baru bulan ini');
        $response->assertSee('Siswa keluar bulan ini');
        $response->assertSee('Murid yang aktif');
        $response->assertSee('Total siswa coding');
        $response->assertSee('Total siswa english');
        $response->assertSee((string) 2, false);
        $response->assertSee((string) 1, false);
        $response->assertSee((string) 2, false);

        Carbon::setTestNow();
    }
}
