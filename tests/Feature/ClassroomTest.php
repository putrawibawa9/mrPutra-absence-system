<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Classroom;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceTeacherFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPayment(Student $student, int $remaining = 5): Payment
    {
        return Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => $remaining,
            'price_amount' => 1000000,
            'amount_paid' => 1000000,
            'payment_date' => now()->toDateString(),
        ]);
    }

    private function makeClassroom(string $format, array $studentIds, string $division = Classroom::DIVISION_ENGLISH): Classroom
    {
        $classroom = Classroom::query()->create([
            'name' => Classroom::makeName($division, $format, Classroom::AGE_KIDS),
            'division' => $division,
            'format' => $format,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => true,
        ]);
        $classroom->students()->sync($studentIds);

        return $classroom;
    }

    public function test_admin_can_create_classroom_with_auto_generated_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('classrooms.store'), [
            'name' => '', // sengaja kosong -> harus auto-generate
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_TEENS_ADULT,
            'is_active' => 1,
            'student_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $classroom = Classroom::query()->firstOrFail();
        $this->assertSame('English · Semi · Teens/Adult', $classroom->name);
        $this->assertSame(Classroom::FORMAT_SEMI, $classroom->format);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $classroom->students->pluck('id')->all());
    }

    public function test_private_format_must_have_exactly_one_student(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);

        $response = $this->from(route('classrooms.create'))->actingAs($admin)->post(route('classrooms.store'), [
            'division' => Classroom::DIVISION_CODING,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => 1,
            'student_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('classrooms.create'));
        $response->assertSessionHasErrors('student_ids');
        $this->assertDatabaseCount('classrooms', 0);
    }

    public function test_semi_classroom_attendance_creates_batch_and_deducts_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);
        $payA = $this->tokenPayment($a);
        $payB = $this->tokenPayment($b);

        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id]);

        $response = $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id, $b->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Semi session.',
        ]);

        $response->assertRedirect(route('attendances.index'));
        $this->assertDatabaseCount('attendance_batches', 1);
        $batch = AttendanceBatch::query()->firstOrFail();
        $this->assertSame(2, Attendance::query()->where('attendance_batch_id', $batch->id)->count());
        $this->assertSame(4, $payA->fresh()->remaining_sessions);
        $this->assertSame(4, $payB->fresh()->remaining_sessions);

        $fees = Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->get();
        $this->assertCount(1, $fees);
        $this->assertSame($batch->id, (int) $fees->first()->attendance_batch_id);
        $this->assertSame(AttendanceTeacherFeeService::FEE_PER_MEETING, (int) $fees->first()->amount);
    }

    public function test_private_classroom_attendance_creates_single_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Solo', 'phone' => '0811', 'is_active' => true]);
        $pay = $this->tokenPayment($a);

        $classroom = $this->makeClassroom(Classroom::FORMAT_PRIVATE, [$a->id]);

        $response = $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id],
            'teaching_minutes' => 60,
        ]);

        $response->assertRedirect(route('attendances.index'));
        $this->assertDatabaseCount('attendance_batches', 0);
        $attendance = Attendance::query()->firstOrFail();
        $this->assertNull($attendance->attendance_batch_id);
        $this->assertSame(4, $pay->fresh()->remaining_sessions);

        $fee = Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->firstOrFail();
        $this->assertSame($attendance->id, (int) $fee->attendance_id);
    }

    public function test_only_present_students_are_recorded_for_self_paced(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);
        $payA = $this->tokenPayment($a);
        $payB = $this->tokenPayment($b);

        // Coding Semi is self-paced: absent students keep their tokens.
        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id], Classroom::DIVISION_CODING);
        $this->assertTrue($classroom->isSelfPaced());

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id], // hanya A hadir
            'teaching_minutes' => 60,
        ])->assertRedirect(route('attendances.index'));

        $this->assertSame(1, Attendance::query()->count());
        $this->assertSame(4, $payA->fresh()->remaining_sessions);
        $this->assertSame(5, $payB->fresh()->remaining_sessions); // B tidak hadir, token utuh
    }
}
