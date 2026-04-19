<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_edit_single_attendance_and_rebalance_tokens(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);

        $studentOne = Student::query()->create([
            'name' => 'Student One',
            'phone' => '0811111111',
            'email' => 'edit1@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '0822222222',
            'email' => 'edit2@example.com',
            'is_active' => true,
        ]);

        $paymentOne = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-001',
            'student_id' => $studentOne->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 4,
            'payment_date' => now()->toDateString(),
        ]);
        $paymentTwo = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-002',
            'student_id' => $studentTwo->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 3,
            'payment_date' => now()->toDateString(),
        ]);

        $attendance = Attendance::query()->create([
            'student_id' => $studentOne->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $paymentOne->id,
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'notes' => 'Initial',
            'learning_journal' => 'Initial journal',
        ]);

        $paymentOne->decrement('remaining_sessions');

        $response = $this->actingAs($teacher)->put(route('attendances.update', $attendance), [
            'student_id' => $studentTwo->id,
            'teacher_ids' => [$teacher->id],
            'payment_id' => $paymentTwo->id,
            'date' => now()->addDay()->toDateString(),
            'teaching_minutes' => 90,
            'notes' => 'Updated',
            'learning_journal' => 'Updated journal content',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $studentTwo->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $paymentTwo->id,
            'teaching_minutes' => 90,
            'notes' => 'Updated',
            'learning_journal' => 'Updated journal content',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentOne->id,
            'remaining_sessions' => 4,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentTwo->id,
            'remaining_sessions' => 2,
        ]);
    }

    public function test_admin_cannot_edit_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student One',
            'phone' => '0811111111',
            'email' => 'blocked-edit@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-003',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 4,
            'payment_date' => now()->toDateString(),
        ]);
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'notes' => 'Initial',
            'learning_journal' => 'Initial journal',
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.edit', $attendance))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('attendances.update', $attendance), [
                'student_id' => $student->id,
                'teacher_ids' => [$teacher->id],
                'payment_id' => $payment->id,
                'date' => now()->addDay()->toDateString(),
                'teaching_minutes' => 90,
                'notes' => 'Blocked update',
                'learning_journal' => 'Blocked journal',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'teacher_id' => $teacher->id,
            'notes' => 'Initial',
            'learning_journal' => 'Initial journal',
        ]);
    }

    public function test_edit_attendance_page_shows_search_based_student_picker(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Editable Search Student',
            'phone' => '0833333333',
            'email' => 'editable-search@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-004',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 4,
            'payment_date' => now()->toDateString(),
        ]);
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'learning_journal' => 'Edit page journal',
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.edit', $attendance));

        $response->assertOk();
        $response->assertSee('Search by student name or phone');
        $response->assertDontSee('Select student');
    }

    public function test_teacher_can_edit_attendance_to_another_student_using_combined_active_tokens(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $studentOne = Student::query()->create([
            'name' => 'First Student',
            'phone' => '0844444441',
            'email' => 'first-student@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Second Student',
            'phone' => '0844444442',
            'email' => 'second-student@example.com',
            'is_active' => true,
        ]);

        $paymentOne = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-005',
            'student_id' => $studentOne->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 4,
            'remaining_sessions' => 3,
            'payment_date' => now()->subDays(3)->toDateString(),
        ]);
        $olderPaymentTwo = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-006',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'payment_date' => now()->subDays(2)->toDateString(),
        ]);
        $newerPaymentTwo = Payment::query()->create([
            'receipt_number' => 'KWT-EDIT-007',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 2,
            'remaining_sessions' => 2,
            'payment_date' => now()->toDateString(),
        ]);

        $attendance = Attendance::query()->create([
            'student_id' => $studentOne->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $paymentOne->id,
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'notes' => 'Initial',
            'learning_journal' => 'Initial journal',
        ]);

        $paymentOne->decrement('remaining_sessions');

        $response = $this->actingAs($teacher)->put(route('attendances.update', $attendance), [
            'student_id' => $studentTwo->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->addDay()->toDateString(),
            'teaching_minutes' => 120,
            'notes' => 'Updated',
            'learning_journal' => 'Updated journal content',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $studentTwo->id,
            'payment_id' => $olderPaymentTwo->id,
            'teaching_minutes' => 120,
            'notes' => 'Updated',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentOne->id,
            'remaining_sessions' => 3,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $olderPaymentTwo->id,
            'remaining_sessions' => 7,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $newerPaymentTwo->id,
            'remaining_sessions' => 2,
        ]);
    }
}
