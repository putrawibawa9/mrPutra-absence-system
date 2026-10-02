<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChurnReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_churn_rate_is_computed_per_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-15 09:00:00'));
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // 8 murid tetap aktif + 2 murid keluar September 2026. Semua daftar 2025.
        for ($i = 1; $i <= 8; $i++) {
            Student::query()->create(['name' => "Aktif $i", 'phone' => "08$i", 'registration_date' => '2025-01-01', 'is_active' => true]);
        }
        Student::query()->create(['name' => 'Keluar 1', 'phone' => '0901', 'registration_date' => '2025-01-01', 'is_active' => false, 'deactivated_at' => '2026-09-10 10:00:00']);
        Student::query()->create(['name' => 'Keluar 2', 'phone' => '0902', 'registration_date' => '2025-01-01', 'is_active' => false, 'deactivated_at' => '2026-09-12 10:00:00']);

        $response = $this->actingAs($admin)->get(route('reports.churn', ['months' => 12]));
        $response->assertOk();

        // September: aktif awal 10, keluar 2 → churn 20%.
        $response->assertViewHas('rows', function ($rows) {
            $sep = $rows->firstWhere('churned', 2);

            return $sep !== null
                && (int) $sep->active_start === 10
                && (float) $sep->rate === 20.0;
        });
        $response->assertViewHas('activeNow', 8);
        $response->assertViewHas('totalChurned', 2);

        Carbon::setTestNow();
    }

    public function test_churn_report_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('reports.churn'))->assertForbidden();
    }
}
