<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_student_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Inactive Candidate',
            'phone' => '0811111111',
            'email' => 'candidate@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('students.toggle-status', $student));

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'is_active' => false,
        ]);
    }

    public function test_inactive_student_cannot_receive_new_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Inactive Student',
            'phone' => '0822222222',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);

        $response = $this->from(route('payments.create'))
            ->actingAs($admin)
            ->post(route('payments.store'), [
                'student_id' => $student->id,
                'package_id' => $package->id,
                'payment_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('payments.create', absolute: false));
        $response->assertSessionHasErrors('student_id');
        $this->assertDatabaseMissing('payments', [
            'student_id' => $student->id,
            'package_id' => $package->id,
        ]);
    }
}
