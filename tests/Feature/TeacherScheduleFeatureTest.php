<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherScheduleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher_schedule_and_teacher_can_view_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Wulan']);
        $student = Student::create([
            'name' => 'Budi',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('teacher-schedules.store'), [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => 'Private English',
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'end_time' => '18:30',
            'notes' => 'Kelas reguler',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('teacher-schedules.index', absolute: false));

        $this->assertDatabaseHas('teacher_schedules', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => 'Private English',
            'day_of_week' => 'monday',
        ]);

        $this->actingAs($teacher)
            ->get(route('my-schedule.index'))
            ->assertOk()
            ->assertSee('Jadwal Mingguan Saya')
            ->assertSee('Senin')
            ->assertSee('17:00 - 18:30')
            ->assertSee('Private English')
            ->assertSee('Budi');
    }

    public function test_admin_cannot_create_overlapping_schedule_for_same_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        TeacherSchedule::create([
            'teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00:00',
            'end_time' => '18:30:00',
            'is_active' => true,
        ]);

        $response = $this->from(route('teacher-schedules.create'))
            ->actingAs($admin)
            ->post(route('teacher-schedules.store'), [
                'teacher_id' => $teacher->id,
                'day_of_week' => 'monday',
                'start_time' => '18:00',
                'end_time' => '19:00',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('teacher-schedules.create', absolute: false));
        $response->assertSessionHasErrors('start_time');
    }

    public function test_schedule_must_fit_active_teacher_availability_when_availability_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        TeacherAvailability::create([
            'teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => true,
        ]);

        $response = $this->from(route('teacher-schedules.create'))
            ->actingAs($admin)
            ->post(route('teacher-schedules.store'), [
                'teacher_id' => $teacher->id,
                'day_of_week' => 'monday',
                'start_time' => '17:00',
                'end_time' => '18:30',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('teacher-schedules.create', absolute: false));
        $response->assertSessionHasErrors('start_time');
    }
}
