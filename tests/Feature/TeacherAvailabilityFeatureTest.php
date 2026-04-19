<?php

namespace Tests\Feature;

use App\Models\TeacherAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAvailabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher_availability(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($admin)->post(route('teacher-availabilities.store'), [
            'teacher_id' => $teacher->id,
            'day_of_week' => 'tuesday',
            'start_time' => '18:30',
            'end_time' => '20:00',
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => '1',
            'notes' => 'Bisa untuk kelas malam',
        ]);

        $response->assertRedirect(route('teacher-availabilities.index', absolute: false));

        $this->assertDatabaseHas('teacher_availabilities', [
            'teacher_id' => $teacher->id,
            'day_of_week' => 'tuesday',
            'status' => TeacherAvailability::STATUS_AVAILABLE,
        ]);
    }

    public function test_teacher_can_manage_own_availability(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($teacher)->post(route('my-availability.store'), [
            'teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'end_time' => '18:30',
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('my-availability.index', absolute: false));

        $this->actingAs($teacher)
            ->get(route('my-availability.index'))
            ->assertOk()
            ->assertSee('Ketersediaan Mengajar Saya')
            ->assertSee('Senin')
            ->assertSee('17:00 - 18:30');
    }

    public function test_teacher_availability_cannot_overlap(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        TeacherAvailability::create([
            'teacher_id' => $teacher->id,
            'day_of_week' => 'wednesday',
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => true,
        ]);

        $response = $this->from(route('teacher-availabilities.create'))
            ->actingAs($admin)
            ->post(route('teacher-availabilities.store'), [
                'teacher_id' => $teacher->id,
                'day_of_week' => 'wednesday',
                'start_time' => '17:30',
                'end_time' => '18:30',
                'status' => TeacherAvailability::STATUS_AVAILABLE,
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('teacher-availabilities.create', absolute: false));
        $response->assertSessionHasErrors('start_time');
    }
}
