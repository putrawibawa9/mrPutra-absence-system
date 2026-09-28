<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_and_reactivate_a_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'is_active' => true]);

        $this->actingAs($admin)->patch(route('teachers.toggle-status', $teacher))->assertRedirect(route('teachers.index'));
        $this->assertFalse($teacher->fresh()->is_active);

        $this->actingAs($admin)->patch(route('teachers.toggle-status', $teacher))->assertRedirect(route('teachers.index'));
        $this->assertTrue($teacher->fresh()->is_active);
    }

    public function test_inactive_teacher_is_hidden_from_schedule_assignment_form(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'GuruAktifX', 'is_active' => true]);
        User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'GuruNonaktifX', 'is_active' => false]);

        $response = $this->actingAs($admin)->get(route('teacher-schedules.create'));
        $response->assertOk();
        $response->assertSee('GuruAktifX');
        $response->assertDontSee('GuruNonaktifX');
    }

    public function test_inactive_teacher_cannot_login(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'username' => 'nonaktif', 'is_active' => false]);

        $this->post(route('login'), ['username' => 'nonaktif'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_active_teacher_can_login(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'username' => 'aktif', 'is_active' => true]);

        $this->post(route('login'), ['username' => 'aktif']);

        $this->assertAuthenticatedAs($teacher);
    }
}
