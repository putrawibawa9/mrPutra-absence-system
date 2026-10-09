<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use App\Models\TeacherSchedule;
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

    public function test_low_token_followup_lists_students_with_whatsapp_reminder(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $low = Student::query()->create(['name' => 'Hampir Habis', 'phone' => '08123456789', 'program_type' => Student::PROGRAM_ENGLISH, 'registration_date' => now()->toDateString(), 'is_active' => true]);
        Payment::query()->create(['student_id' => $low->id, 'source_type' => Payment::SOURCE_TOKEN, 'total_sessions' => 8, 'remaining_sessions' => 1, 'price_amount' => 800000, 'amount_paid' => 800000, 'payment_date' => now()->toDateString()]);

        $habis = Student::query()->create(['name' => 'Token Habis', 'phone' => '08987654321', 'program_type' => Student::PROGRAM_CODING, 'registration_date' => now()->toDateString(), 'is_active' => true]);
        Payment::query()->create(['student_id' => $habis->id, 'source_type' => Payment::SOURCE_TOKEN, 'total_sessions' => 8, 'remaining_sessions' => 0, 'price_amount' => 800000, 'amount_paid' => 800000, 'payment_date' => now()->toDateString()]);

        $aman = Student::query()->create(['name' => 'Masih Banyak', 'phone' => '08111222333', 'program_type' => Student::PROGRAM_ENGLISH, 'registration_date' => now()->toDateString(), 'is_active' => true]);
        Payment::query()->create(['student_id' => $aman->id, 'source_type' => Payment::SOURCE_TOKEN, 'total_sessions' => 8, 'remaining_sessions' => 5, 'price_amount' => 800000, 'amount_paid' => 800000, 'payment_date' => now()->toDateString()]);

        $response = $this->actingAs($admin)->get(route('follow-up.low-token'));

        $response->assertOk();
        $response->assertViewHas('lowTokenStudents', function ($students) use ($low, $habis, $aman) {
            $ids = $students->pluck('id');

            return $ids->contains($low->id)
                && $ids->contains($habis->id)
                && ! $ids->contains($aman->id)
                && (int) $students->first()->id === $habis->id; // paling mendesak (0) di atas
        });
        $response->assertSee('Token Menipis');
        $response->assertSee('Hampir Habis');
        $response->assertSee('wa.me'); // tombol WA sekali klik tersedia
        $response->assertDontSee('Masih Banyak');
    }

    public function test_dashboard_can_show_classes_for_another_day(): void
    {
        Carbon::setTestNow('2026-10-08 09:00:00'); // Kamis
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Pak Guru']);

        $classroom = Classroom::query()->create([
            'name' => 'Kelas Jumat',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_TEENS_ADULT,
            'is_active' => true,
        ]);
        TeacherSchedule::query()->create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'friday',
            'start_time' => '17:00',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        // Hari ini (Kamis) tidak ada kelas itu.
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas Hari Ini')
            ->assertDontSee('Kelas Jumat');

        // Besok (Jumat, 9 Okt) kelas itu muncul.
        $this->actingAs($admin)->get(route('dashboard', ['date' => '2026-10-09']))
            ->assertOk()
            ->assertSee('Jadwal Kelas')
            ->assertSee('Kelas Jumat');

        Carbon::setTestNow();
    }

    public function test_dashboard_hides_inactive_classroom_schedules(): void
    {
        Carbon::setTestNow('2026-10-08 09:00:00'); // Kamis
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        foreach ([['Kelas Aktif', true], ['Kelas Nonaktif', false]] as [$name, $active]) {
            $classroom = Classroom::query()->create([
                'name' => $name,
                'division' => Classroom::DIVISION_ENGLISH,
                'format' => Classroom::FORMAT_SEMI,
                'age_group' => Classroom::AGE_TEENS_ADULT,
                'is_active' => $active,
            ]);
            TeacherSchedule::query()->create([
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => 'thursday',
                'start_time' => '17:00',
                'end_time' => '18:30',
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas Aktif')
            ->assertDontSee('Kelas Nonaktif');

        Carbon::setTestNow();
    }
}
