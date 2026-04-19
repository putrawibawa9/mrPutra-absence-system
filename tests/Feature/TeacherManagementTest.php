<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('teachers.store'), [
            'name' => 'Teacher Baru',
            'email' => 'teacherbaru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('teachers.index', absolute: false));
        $this->assertDatabaseHas('users', [
            'name' => 'Teacher Baru',
            'email' => 'teacherbaru@example.com',
            'role' => User::ROLE_TEACHER,
        ]);
    }
}
