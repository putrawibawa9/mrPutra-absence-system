<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTeacherSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_uses_logged_in_user_as_teacher(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student One',
            'phone' => '0819999999',
            'email' => 'teacherselect@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-SELECT-001',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'learning_journal' => 'Practiced conversation and pronunciation.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'teaching_minutes' => 60,
        ]);
    }

    public function test_admin_cannot_record_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '0829999999',
            'email' => 'adminblocked@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-SELECT-002',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('attendances.store'), [
                'mode' => 'single',
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'teacher_ids' => [$admin->id],
                'date' => now()->toDateString(),
                'teaching_minutes' => 60,
                'learning_journal' => 'Admin should not be able to record attendance.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
        ]);
    }

    public function test_single_attendance_can_store_multiple_teachers(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Co Taught Student',
            'phone' => '0819888888',
            'email' => 'cotaught@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-SELECT-003',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_ids' => [$teacher->id, $coTeacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 105,
            'learning_journal' => 'Private lesson taught by two teachers.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));

        $attendance = \App\Models\Attendance::query()->with('teachers')->first();

        $this->assertNotNull($attendance);
        $this->assertSame([$teacher->id, $coTeacher->id], $attendance->teachers->pluck('id')->sort()->values()->all());
    }
}
