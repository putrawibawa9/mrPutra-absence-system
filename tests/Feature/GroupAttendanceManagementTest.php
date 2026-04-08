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
            'student_ids' => [$studentOne->id, $studentTwo->id],
            'date' => now()->toDateString(),
            'notes' => 'Evening class',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendance_batches', [
            'title' => 'Group English A',
            'teacher_id' => $teacher->id,
        ]);

        $batch = AttendanceBatch::query()->first();

        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentOne->id,
            'payment_id' => $paymentOne->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentTwo->id,
            'payment_id' => $paymentTwo->id,
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
}
