<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncTeacherFeeExpensesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_backfills_teacher_fee_expenses_for_existing_attendances(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Fee Teacher']);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Co Fee Teacher']);
        $student = Student::query()->create([
            'name' => 'Backfill Student',
            'phone' => '0819000001',
            'email' => 'backfill@example.com',
            'is_active' => true,
        ]);

        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'Single backfill',
        ]);
        $attendance->teachers()->sync([$teacher->id]);

        $batch = AttendanceBatch::query()->create([
            'title' => 'Backfill Batch',
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'Batch backfill',
        ]);
        $batch->teachers()->sync([$teacher->id, $coTeacher->id]);
        Attendance::query()->create([
            'attendance_batch_id' => $batch->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'Batch backfill attendance',
        ]);

        $this->artisan('attendance:sync-teacher-fees', ['--user-id' => $teacher->id])
            ->expectsOutput('Teacher fee expenses synced successfully.')
            ->assertSuccessful();

        $this->assertSame(3, Expense::query()->count());
        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Fee Guru',
        ]);
        $this->assertDatabaseHas('expenses', [
            'attendance_id' => $attendance->id,
            'teacher_user_id' => $teacher->id,
            'amount' => 40000,
        ]);
        $this->assertDatabaseHas('expenses', [
            'attendance_batch_id' => $batch->id,
            'teacher_user_id' => $teacher->id,
            'amount' => 40000,
        ]);
        $this->assertDatabaseHas('expenses', [
            'attendance_batch_id' => $batch->id,
            'teacher_user_id' => $coTeacher->id,
            'amount' => 40000,
        ]);
    }
}
