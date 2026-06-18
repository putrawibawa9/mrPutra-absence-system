<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\ExpenseCategory;
use App\Models\MaterialLink;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_index_defaults_to_current_month(): void
    {
        $teacher = User::factory()->create(['name' => 'Teacher One', 'role' => User::ROLE_TEACHER]);        $student = Student::query()->create([
            'name' => 'Today Student',
            'phone' => '0811111111',
            'email' => 'today@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-FILTER-TODAY',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'payment_date' => now()->toDateString(),
        ]);

        $currentMonthDate = now()->startOfMonth()->addDays(2)->toDateString();
        $previousMonthDate = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => $currentMonthDate,
            'notes' => 'Today note',
            'learning_journal' => 'Today learning journal',
        ]);
        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => $previousMonthDate,
            'notes' => 'Yesterday note',
            'learning_journal' => 'Yesterday learning journal',
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('Today note');
        $response->assertDontSee('Yesterday note');
        $response->assertSee('value="'.now()->startOfMonth()->toDateString().'"', false);
        $response->assertSee('value="'.now()->endOfMonth()->toDateString().'"', false);
    }

    public function test_attendance_index_can_be_filtered_by_date_student_and_teacher(): void
    {
        $teacherOne = User::factory()->create(['name' => 'Teacher One', 'role' => User::ROLE_TEACHER]);
        $teacherTwo = User::factory()->create(['name' => 'Teacher Two', 'role' => User::ROLE_TEACHER]);
        $studentOne = Student::query()->create([
            'name' => 'Filtered Student',
            'phone' => '0811111111',
            'email' => 'filtered@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Hidden Student',
            'phone' => '0822222222',
            'email' => 'hidden@example.com',
            'is_active' => true,
        ]);

        $paymentOne = Payment::query()->create([
            'receipt_number' => 'KWT-FILTER-001',
            'student_id' => $studentOne->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'payment_date' => '2026-04-01',
        ]);
        $paymentTwo = Payment::query()->create([
            'receipt_number' => 'KWT-FILTER-002',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'payment_date' => '2026-04-01',
        ]);

        Attendance::query()->create([
            'student_id' => $studentOne->id,
            'teacher_id' => $teacherOne->id,
            'payment_id' => $paymentOne->id,
            'date' => '2026-04-10',
            'notes' => 'Visible note',
            'learning_journal' => 'Visible learning journal',
        ]);
        Attendance::query()->create([
            'student_id' => $studentTwo->id,
            'teacher_id' => $teacherTwo->id,
            'payment_id' => $paymentTwo->id,
            'date' => '2026-04-01',
            'notes' => 'Hidden note',
            'learning_journal' => 'Hidden learning journal',
        ]);

        $response = $this->actingAs($teacherOne)->get(route('attendances.index', [
            'date_from' => '2026-04-09',
            'date_to' => '2026-04-10',
            'student_id' => $studentOne->id,
            'teacher_id' => $teacherOne->id,
        ]));

        $response->assertOk();
        $response->assertSee('Visible note');
        $response->assertDontSee('Hidden note');
    }

    public function test_attendance_index_can_search_by_material_link(): void
    {
        $teacher = User::factory()->create(['name' => 'Search Teacher', 'role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Search Student',
            'phone' => '0813555000',
            'email' => 'search-student@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-SEARCH-001',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 4,
            'remaining_sessions' => 4,
            'payment_date' => now()->toDateString(),
        ]);
        $matchingMaterial = MaterialLink::create([
            'title' => 'Chunk Review Pack',
            'url' => 'https://example.com/chunk-review',
            'is_active' => true,
        ]);
        $otherMaterial = MaterialLink::create([
            'title' => 'Coding Variables Sheet',
            'url' => 'https://example.com/coding-variables',
            'is_active' => true,
        ]);

        $matchingAttendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'notes' => 'Matching note',
            'learning_journal' => 'Matching learning journal',
        ]);
        $matchingAttendance->materialLinks()->sync([$matchingMaterial->id]);

        $otherAttendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'notes' => 'Other note',
            'learning_journal' => 'Other learning journal',
        ]);
        $otherAttendance->materialLinks()->sync([$otherMaterial->id]);

        $response = $this->actingAs($teacher)->get(route('attendances.index', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'search' => 'Chunk Review',
        ]));

        $response->assertOk();
        $response->assertSee('Matching note');
        $response->assertDontSee('Other note');
        $response->assertSee('Chunk Review Pack');
        $response->assertDontSee('Coding Variables Sheet');
    }

    public function test_group_attendance_is_displayed_once_on_attendance_index(): void
    {
        $teacher = User::factory()->create(['name' => 'Teacher Group', 'role' => User::ROLE_TEACHER]);        $studentOne = Student::query()->create([
            'name' => 'Group Student One',
            'phone' => '0811231231',
            'email' => 'groupstudent1@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Group Student Two',
            'phone' => '0811231232',
            'email' => 'groupstudent2@example.com',
            'is_active' => true,
        ]);
        $paymentOne = Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-001',
            'student_id' => $studentOne->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'payment_date' => now()->toDateString(),
        ]);
        $paymentTwo = Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-002',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'payment_date' => now()->toDateString(),
        ]);
        $batch = AttendanceBatch::query()->create([
            'title' => 'Group English A',
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'notes' => 'Evening class',
            'learning_journal' => 'Group lesson',
        ]);

        Attendance::query()->create([
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentOne->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $paymentOne->id,
            'date' => now()->toDateString(),
            'notes' => 'Evening class',
            'learning_journal' => 'Group lesson',
        ]);
        Attendance::query()->create([
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentTwo->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $paymentTwo->id,
            'date' => now()->toDateString(),
            'notes' => 'Evening class',
            'learning_journal' => 'Group lesson',
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Group Student One, Group Student Two');
        $this->assertCount(1, $response->viewData('attendances')->items());
    }

    public function test_attendance_filter_can_find_group_class_by_co_teacher(): void
    {
        $primaryTeacher = User::factory()->create(['name' => 'Primary Teacher', 'role' => User::ROLE_TEACHER]);
        $coTeacher = User::factory()->create(['name' => 'Co Teacher', 'role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Grouped Student',
            'phone' => '0812300001',
            'email' => 'grouped-student@example.com',
            'is_active' => true,
        ]);

        $batch = AttendanceBatch::query()->create([
            'title' => 'Saturday Group',
            'teacher_id' => $primaryTeacher->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'Collaborative class',
        ]);
        $batch->teachers()->sync([$primaryTeacher->id, $coTeacher->id]);

        Attendance::query()->create([
            'attendance_batch_id' => $batch->id,
            'student_id' => $student->id,
            'teacher_id' => $primaryTeacher->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'Collaborative class',
        ]);

        $response = $this->actingAs($primaryTeacher)->get(route('attendances.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'teacher_id' => $coTeacher->id,
        ]));

        $response->assertOk();
        $response->assertSee('Saturday Group');
        $response->assertSee('Primary Teacher, Co Teacher');
    }

    public function test_attendance_index_shows_teacher_teaching_recap_for_period(): void
    {
        $teacherOne = User::factory()->create(['name' => 'Teacher Recap One', 'role' => User::ROLE_TEACHER]);
        $teacherTwo = User::factory()->create(['name' => 'Teacher Recap Two', 'role' => User::ROLE_TEACHER]);
        $studentOne = Student::query()->create([
            'name' => 'Recap Student One',
            'phone' => '0812200001',
            'email' => 'recap-student-one@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Recap Student Two',
            'phone' => '0812200002',
            'email' => 'recap-student-two@example.com',
            'is_active' => true,
        ]);

        $singleAttendance = Attendance::query()->create([
            'student_id' => $studentOne->id,
            'teacher_id' => $teacherOne->id,
            'date' => '2026-04-10',
            'teaching_minutes' => 90,
            'learning_journal' => 'Single recap session',
        ]);
        $singleAttendance->teachers()->sync([$teacherOne->id]);

        $batch = AttendanceBatch::query()->create([
            'title' => 'Recap Group',
            'teacher_id' => $teacherOne->id,
            'date' => '2026-04-10',
            'teaching_minutes' => 120,
            'learning_journal' => 'Group recap session',
        ]);
        $batch->teachers()->sync([$teacherOne->id, $teacherTwo->id]);

        Attendance::query()->create([
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentOne->id,
            'teacher_id' => $teacherOne->id,
            'date' => '2026-04-10',
            'teaching_minutes' => 120,
            'learning_journal' => 'Group recap session',
        ]);
        Attendance::query()->create([
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentTwo->id,
            'teacher_id' => $teacherOne->id,
            'date' => '2026-04-10',
            'teaching_minutes' => 120,
            'learning_journal' => 'Group recap session',
        ]);

        $response = $this->actingAs($teacherOne)->get(route('attendances.index', [
            'date_from' => '2026-04-10',
            'date_to' => '2026-04-10',
        ]));

        $response->assertOk();
        $response->assertSee('Rekap Mengajar Guru');
        $response->assertSee('Teacher Recap One');
        $response->assertSee('Teacher Recap Two');
        $response->assertSee('2'); // Teacher One sessions: 1 single + 1 group
        $response->assertSee('1'); // Teacher Two sessions: 1 group
        $response->assertSee('3h 30m'); // Teacher One minutes: 90 + 120
        $response->assertSee('2h'); // Teacher Two minutes: 120
    }

    public function test_attendance_index_can_filter_by_teacher_fee_status(): void
    {
        $teacher = User::factory()->create(['name' => 'Fee Teacher', 'role' => User::ROLE_TEACHER]);
        $studentWithFee = Student::query()->create([
            'name' => 'With Fee Student',
            'phone' => '0812400001',
            'email' => 'withfee@example.com',
            'is_active' => true,
        ]);
        $studentWithoutFee = Student::query()->create([
            'name' => 'Without Fee Student',
            'phone' => '0812400002',
            'email' => 'withoutfee@example.com',
            'is_active' => true,
        ]);

        $attendanceWithFee = Attendance::query()->create([
            'student_id' => $studentWithFee->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-10',
            'learning_journal' => 'Synced fee journal',
        ]);
        $attendanceWithFee->teachers()->sync([$teacher->id]);

        $attendanceWithoutFee = Attendance::query()->create([
            'student_id' => $studentWithoutFee->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-10',
            'learning_journal' => 'Missing fee journal',
        ]);
        $attendanceWithoutFee->teachers()->sync([$teacher->id]);

        $feeCategory = ExpenseCategory::query()->create([
            'name' => 'Fee Guru',
            'is_active' => true,
        ]);

        $attendanceWithFee->expenses()->create([
            'expense_category_id' => $feeCategory->id,
            'created_by_user_id' => $teacher->id,
            'teacher_user_id' => $teacher->id,
            'title' => 'Fee guru - Fee Teacher - With Fee Student',
            'amount' => 40000,
            'expense_date' => '2026-04-10',
            'notes' => 'Otomatis dari attendance siswa.',
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.index', [
            'date_from' => '2026-04-10',
            'date_to' => '2026-04-10',
            'teacher_fee_status' => 'without_fee',
        ]));

        $response->assertOk();
        $response->assertSee('Without Fee Student');
        $response->assertSee('Belum');
        $entries = $response->viewData('attendances')->items();
        $this->assertCount(1, $entries);
        $this->assertSame('Without Fee Student', $entries[0]->student_label);
    }
}
