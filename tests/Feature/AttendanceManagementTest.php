<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_record_attendance_and_consume_one_session(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student One',
            'phone' => '08123456789',
            'email' => 'student@example.com',
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 2,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'notes' => 'Present',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'remaining_sessions' => 1,
        ]);
    }

    public function test_attendance_cannot_be_recorded_when_payment_has_no_remaining_sessions(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '08987654321',
            'email' => 'student2@example.com',
        ]);
        $package = Package::query()->create([
            'name' => '5 Sessions',
            'total_sessions' => 5,
            'price' => 300000,
        ]);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
            'total_sessions' => 5,
            'remaining_sessions' => 0,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->from(route('attendances.create', ['student_id' => $student->id]))
            ->actingAs($teacher)
            ->post(route('attendances.store'), [
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('attendances.create', ['student_id' => $student->id], absolute: false));
        $response->assertSessionHasErrors('payment_id');
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'remaining_sessions' => 0,
        ]);
    }
}
