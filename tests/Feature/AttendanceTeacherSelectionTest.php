<?php

namespace Tests\Feature;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTeacherSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_uses_selected_teacher_as_primary_teacher(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student One',
            'phone' => '0819999999',
            'email' => 'teacherselect@example.com',
            'is_active' => true,
        ]);        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-SELECT-001',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
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
        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Fee Guru',
        ]);
        $feeCategory = ExpenseCategory::query()->where('name', 'Fee Guru')->firstOrFail();
        $attendance = \App\Models\Attendance::query()->firstOrFail();
        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $feeCategory->id,
            'attendance_id' => $attendance->id,
            'teacher_user_id' => $teacher->id,
            'amount' => 40000,
        ]);
    }

    public function test_admin_can_record_attendance_for_selected_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '0829999999',
            'email' => 'adminblocked@example.com',
            'is_active' => true,
        ]);        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-SELECT-002',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('attendances.store'), [
                'mode' => 'single',
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'teacher_ids' => [$teacher->id],
                'date' => now()->toDateString(),
                'teaching_minutes' => 60,
                'learning_journal' => 'Admin recorded attendance for assigned teacher.',
            ])
            ->assertRedirect(route('attendances.index', absolute: false));

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_id' => $teacher->id,
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
        $this->assertSame(2, Expense::query()->where('attendance_id', $attendance->id)->count());
    }
}
