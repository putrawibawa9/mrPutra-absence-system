<?php

namespace Tests\Feature;

use App\Models\AttendanceBatch;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupAttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_record_group_attendance_for_multiple_students(): void
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
            'email' => 'group1@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '0822222222',
            'email' => 'group2@example.com',
            'is_active' => true,
        ]);

        $paymentOne = Payment::query()->create([
            'student_id' => $studentOne->id,
            'package_id' => $package->id,
            'receipt_number' => 'KWT-TEST-001',
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 3,
            'payment_date' => now()->toDateString(),
        ]);
        $paymentTwo = Payment::query()->create([
            'student_id' => $studentTwo->id,
            'package_id' => $package->id,
            'receipt_number' => 'KWT-TEST-002',
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 2,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Group English A',
            'group_teacher_ids' => [$teacher->id],
            'student_ids' => [$studentOne->id, $studentTwo->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 120,
            'learning_journal' => 'Group practiced reading comprehension and speaking drills.',
            'notes' => 'Evening class',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendance_batches', [
            'title' => 'Group English A',
            'teacher_id' => $teacher->id,
            'teaching_minutes' => 120,
            'learning_journal' => 'Group practiced reading comprehension and speaking drills.',
        ]);

        $batch = AttendanceBatch::query()->first();

        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentOne->id,
            'payment_id' => $paymentOne->id,
            'teaching_minutes' => 120,
        ]);
        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentTwo->id,
            'payment_id' => $paymentTwo->id,
            'teaching_minutes' => 120,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentOne->id,
            'remaining_sessions' => 2,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentTwo->id,
            'remaining_sessions' => 1,
        ]);
    }

    public function test_group_attendance_can_record_token_debt_for_student_without_tokens(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Group Debt Student',
            'phone' => '0833333333',
            'email' => 'groupdebt@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Debt Group',
            'group_teacher_ids' => [$teacher->id],
            'student_ids' => [$student->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 75,
            'learning_journal' => 'Group lesson recorded as debt.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $batch = AttendanceBatch::query()->first();

        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $student->id,
            'payment_id' => null,
            'teaching_minutes' => 75,
            'learning_journal' => 'Group lesson recorded as debt.',
        ]);
    }

    public function test_teacher_can_record_group_attendance_with_multiple_teachers(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Collaborative Student',
            'phone' => '0833000001',
            'email' => 'collab@example.com',
            'is_active' => true,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-CO-1',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Collaborative Class',
            'group_teacher_ids' => [$teacher->id, $coTeacher->id],
            'student_ids' => [$student->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 90,
            'learning_journal' => 'Class taught by two teachers.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));

        $batch = AttendanceBatch::query()->with('teachers')->first();

        $this->assertNotNull($batch);
        $this->assertSame([$teacher->id, $coTeacher->id], $batch->teachers->pluck('id')->sort()->values()->all());
    }
}
