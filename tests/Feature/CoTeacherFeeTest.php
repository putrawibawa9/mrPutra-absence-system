<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceTeacherFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoTeacherFeeTest extends TestCase
{
    use RefreshDatabase;

    private function privateClassroomWithStudent(): array
    {
        $classroom = Classroom::query()->create([
            'name' => 'English · Private · Kids',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
        ]);
        $student = Student::query()->create([
            'name' => 'Devana', 'phone' => '0811', 'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(), 'is_active' => true,
        ]);
        $classroom->students()->attach($student->id);

        return [$classroom, $student];
    }

    public function test_co_teacher_gets_custom_fee_expense_alongside_primary_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Utama']);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Trainee']);
        [$classroom, $student] = $this->privateClassroomWithStudent();

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => '2026-09-23',
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$student->id],
            'learning_journal' => 'Belajar present tense',
            'co_teacher_id' => [$coTeacher->id],
            'co_teacher_fee' => [25000],
        ])->assertRedirect(route('attendances.index'));

        $attendance = Attendance::query()->firstOrFail();

        // Dua expense fee guru: utama 40.000 + co-teacher custom 25.000.
        $fees = Expense::query()->where('attendance_id', $attendance->id)->get();
        $this->assertCount(2, $fees);
        $this->assertSame(40000, (int) $fees->firstWhere('teacher_user_id', $teacher->id)->amount);

        $coFee = $fees->firstWhere('teacher_user_id', $coTeacher->id);
        $this->assertSame(25000, (int) $coFee->amount);
        $this->assertStringContainsString('Fee co-teacher', $coFee->title);
        $this->assertSame(ExpenseCategory::FEE_GURU_NAME, $coFee->category->name);

        // Pivot menyimpan role & fee custom.
        $pivot = $attendance->teachers()->where('users.id', $coTeacher->id)->first()->pivot;
        $this->assertSame('co_teacher', $pivot->role);
        $this->assertSame(25000, (int) $pivot->fee_amount);
    }

    public function test_co_teacher_without_salary_is_recorded_but_creates_no_fee(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$classroom, $student] = $this->privateClassroomWithStudent();

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => '2026-09-23',
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$student->id],
            'learning_journal' => 'Sesi training',
            'co_teacher_id' => [$coTeacher->id],
            'co_teacher_fee' => [''], // belum ditentukan
        ])->assertRedirect(route('attendances.index'));

        $attendance = Attendance::query()->firstOrFail();

        // Hanya fee guru utama yang jadi expense.
        $fees = Expense::query()->where('attendance_id', $attendance->id)->get();
        $this->assertCount(1, $fees);
        $this->assertSame($teacher->id, (int) $fees->first()->teacher_user_id);

        // Co-teacher tetap tercatat di pivot dengan fee 0.
        $pivot = $attendance->teachers()->where('users.id', $coTeacher->id)->first()->pivot;
        $this->assertSame('co_teacher', $pivot->role);
        $this->assertSame(0, (int) $pivot->fee_amount);

        // Fee dianggap lengkap (co-teacher tanpa salary tidak diwajibkan).
        $attendance->load(['teachers', 'expenses']);
        $this->assertTrue(app(AttendanceTeacherFeeService::class)->hasCompleteAttendanceFee($attendance));
    }

    public function test_editing_co_teacher_fee_later_updates_expense(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        [$classroom, $student] = $this->privateClassroomWithStudent();

        // Rekam dulu tanpa salary co-teacher.
        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => '2026-09-23',
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$student->id],
            'learning_journal' => 'Sesi training',
            'co_teacher_id' => [$coTeacher->id],
            'co_teacher_fee' => [''],
        ]);
        $attendance = Attendance::query()->firstOrFail();
        $this->assertCount(1, Expense::query()->where('attendance_id', $attendance->id)->get());

        // Owner menentukan salary lewat edit attendance.
        $this->actingAs($admin)->put(route('attendances.update', $attendance), [
            'student_id' => $student->id,
            'teacher_ids' => [$teacher->id],
            'date' => '2026-09-23',
            'teaching_minutes' => 60,
            'learning_journal' => 'Sesi training',
            'co_teacher_id' => [$coTeacher->id],
            'co_teacher_fee' => [30000],
        ])->assertRedirect(route('attendances.index'));

        $coFee = Expense::query()->where('attendance_id', $attendance->id)->where('teacher_user_id', $coTeacher->id)->first();
        $this->assertNotNull($coFee);
        $this->assertSame(30000, (int) $coFee->amount);
    }
}
