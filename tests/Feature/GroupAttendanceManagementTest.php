<?php

namespace Tests\Feature;

use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\MaterialLink;
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
            'receipt_number' => 'KWT-TEST-001',
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 3,
            'payment_date' => now()->toDateString(),
        ]);
        $paymentTwo = Payment::query()->create([
            'student_id' => $studentTwo->id,
            'receipt_number' => 'KWT-TEST-002',
            'source_type' => Payment::SOURCE_TOKEN,
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
        $this->assertSame(2, Expense::query()->where('attendance_batch_id', $batch->id)->count());
    }

    public function test_teacher_can_record_group_attendance_with_different_learning_journal_for_each_student(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $studentOne = Student::query()->create([
            'name' => 'English Focus Student',
            'phone' => '0844000001',
            'email' => 'englishfocus@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Coding Focus Student',
            'phone' => '0844000002',
            'email' => 'codingfocus@example.com',
            'is_active' => true,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-JOURNAL-1',
            'student_id' => $studentOne->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);
        Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-JOURNAL-2',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Mixed Progress Class',
            'group_teacher_ids' => [$teacher->id],
            'group_journal_mode' => 'per_student',
            'student_ids' => [$studentOne->id, $studentTwo->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 90,
            'student_learning_journals' => [
                $studentOne->id => 'Reading practice and vocabulary review for English class.',
                $studentTwo->id => 'Scratch project debugging and variable exercise for coding class.',
            ],
            'notes' => 'Different learning focus inside one group session.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));

        $this->assertDatabaseHas('attendance_batches', [
            'title' => 'Mixed Progress Class',
            'learning_journal' => 'Individual learning journals recorded for each student.',
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentOne->id,
            'learning_journal' => 'Reading practice and vocabulary review for English class.',
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentTwo->id,
            'learning_journal' => 'Scratch project debugging and variable exercise for coding class.',
        ]);
    }

    public function test_admin_can_edit_group_attendance_and_sync_students_teachers_and_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacherOne = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherTwo = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $studentOne = Student::query()->create([
            'name' => 'Batch Student One',
            'phone' => '0855000001',
            'email' => 'batch-one@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Batch Student Two',
            'phone' => '0855000002',
            'email' => 'batch-two@example.com',
            'is_active' => true,
        ]);
        $studentThree = Student::query()->create([
            'name' => 'Batch Student Three',
            'phone' => '0855000003',
            'email' => 'batch-three@example.com',
            'is_active' => true,
        ]);

        $paymentOne = Payment::query()->create([
            'receipt_number' => 'KWT-BATCH-EDIT-1',
            'student_id' => $studentOne->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 4,
            'payment_date' => now()->subDay()->toDateString(),
        ]);
        $paymentTwo = Payment::query()->create([
            'receipt_number' => 'KWT-BATCH-EDIT-2',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 4,
            'payment_date' => now()->subDay()->toDateString(),
        ]);
        $paymentThree = Payment::query()->create([
            'receipt_number' => 'KWT-BATCH-EDIT-3',
            'student_id' => $studentThree->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $batch = AttendanceBatch::query()->create([
            'title' => 'Original Batch',
            'teacher_id' => $teacherOne->id,
            'date' => now()->toDateString(),
            'teaching_minutes' => 90,
            'notes' => 'Original notes',
            'learning_journal' => 'Original group journal.',
        ]);
        $batch->teachers()->sync([$teacherOne->id]);
        $batch->attendances()->createMany([
            [
                'student_id' => $studentOne->id,
                'teacher_id' => $teacherOne->id,
                'payment_id' => $paymentOne->id,
                'date' => now()->toDateString(),
                'teaching_minutes' => 90,
                'notes' => 'Original notes',
                'learning_journal' => 'Student one original journal.',
            ],
            [
                'student_id' => $studentTwo->id,
                'teacher_id' => $teacherOne->id,
                'payment_id' => $paymentTwo->id,
                'date' => now()->toDateString(),
                'teaching_minutes' => 90,
                'notes' => 'Original notes',
                'learning_journal' => 'Student two original journal.',
            ],
        ]);
        $paymentOne->decrement('remaining_sessions');
        $paymentTwo->decrement('remaining_sessions');

        $this->actingAs($admin)
            ->get(route('attendances.batches.edit', $batch))
            ->assertOk();

        $response = $this->actingAs($admin)->put(route('attendances.batches.update', $batch), [
            'mode' => 'group',
            'group_title' => 'Updated Batch',
            'group_teacher_ids' => [$teacherTwo->id, $teacherOne->id],
            'group_journal_mode' => 'per_student',
            'student_ids' => [$studentOne->id, $studentThree->id],
            'date' => now()->addDay()->toDateString(),
            'teaching_minutes' => 120,
            'student_learning_journals' => [
                $studentOne->id => 'Student one updated journal.',
                $studentThree->id => 'Student three new journal.',
            ],
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));

        $this->assertDatabaseHas('attendance_batches', [
            'id' => $batch->id,
            'title' => 'Updated Batch',
            'teacher_id' => $teacherTwo->id,
            'teaching_minutes' => 120,
            'learning_journal' => 'Individual learning journals recorded for each student.',
            'notes' => 'Updated notes',
        ]);
        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentOne->id,
            'teacher_id' => $teacherTwo->id,
            'payment_id' => $paymentOne->id,
            'learning_journal' => 'Student one updated journal.',
            'teaching_minutes' => 120,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentTwo->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $studentThree->id,
            'teacher_id' => $teacherTwo->id,
            'payment_id' => $paymentThree->id,
            'learning_journal' => 'Student three new journal.',
            'teaching_minutes' => 120,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentTwo->id,
            'remaining_sessions' => 4,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentThree->id,
            'remaining_sessions' => 4,
        ]);
        $this->assertDatabaseHas('expenses', [
            'attendance_batch_id' => $batch->id,
            'teacher_user_id' => $teacherTwo->id,
            'amount' => 40000,
        ]);
        $this->assertDatabaseMissing('expenses', [
            'attendance_batch_id' => $batch->id,
            'teacher_user_id' => $teacherOne->id,
            'title' => 'Fee guru - '.$teacherOne->name.' - Original Batch',
        ]);
    }

    public function test_group_attendance_requires_learning_journal_for_each_selected_student_when_using_per_student_mode(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Needs Journal Student',
            'phone' => '0844000003',
            'email' => 'needsjournal@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->from(route('attendances.create'))->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Journal Validation Class',
            'group_teacher_ids' => [$teacher->id],
            'group_journal_mode' => 'per_student',
            'student_ids' => [$student->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'student_learning_journals' => [
                $student->id => '',
            ],
        ]);

        $response->assertRedirect(route('attendances.create', absolute: false));
        $response->assertSessionHasErrors([
            'student_learning_journals.'.$student->id,
        ]);
    }

    public function test_group_attendance_can_store_homework_per_student(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $studentOne = Student::query()->create([
            'name' => 'Homework Batch Student One',
            'phone' => '0844000011',
            'email' => 'homework-batch-one@example.com',
            'is_active' => true,
        ]);
        $studentTwo = Student::query()->create([
            'name' => 'Homework Batch Student Two',
            'phone' => '0844000012',
            'email' => 'homework-batch-two@example.com',
            'is_active' => true,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-HW-1',
            'student_id' => $studentOne->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);
        Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-HW-2',
            'student_id' => $studentTwo->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Homework Group Class',
            'group_teacher_ids' => [$teacher->id],
            'group_journal_mode' => 'per_student',
            'group_homework_mode' => 'per_student',
            'student_ids' => [$studentOne->id, $studentTwo->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 90,
            'student_learning_journals' => [
                $studentOne->id => 'Student one speaking practice.',
                $studentTwo->id => 'Student two reading practice.',
            ],
            'student_homework_contents' => [
                $studentOne->id => rtrim(str_repeat("Chunk A for student one.\n", 10)),
                $studentTwo->id => rtrim(str_repeat("Chunk B for student two.\n", 10)),
            ],
            'notes' => 'Homework differs per student.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));

        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentOne->id,
            'homework_content' => rtrim(str_repeat("Chunk A for student one.\n", 10)),
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentTwo->id,
            'homework_content' => rtrim(str_repeat("Chunk B for student two.\n", 10)),
        ]);
    }

    public function test_group_attendance_can_store_optional_material_links(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Grouped Material Student',
            'phone' => '0844000013',
            'email' => 'grouped-material@example.com',
            'is_active' => true,
        ]);
        Payment::query()->create([
            'receipt_number' => 'KWT-GROUP-MATERIAL-1',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);
        $materialLink = MaterialLink::create([
            'title' => 'Group Speaking Material',
            'url' => 'https://example.com/group-speaking-material',
            'is_active' => true,
        ]);
        $secondMaterialLink = MaterialLink::create([
            'title' => 'Group Reading Material',
            'url' => 'https://example.com/group-reading-material',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'group',
            'group_title' => 'Material Link Batch',
            'group_teacher_ids' => [$teacher->id],
            'group_material_link_ids' => [$materialLink->id, $secondMaterialLink->id],
            'group_journal_mode' => 'group',
            'student_ids' => [$student->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'learning_journal' => 'Shared material lesson.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $batch = AttendanceBatch::query()->firstOrFail();

        $this->assertDatabaseHas('attendance_batches', [
            'id' => $batch->id,
            'material_link_id' => $materialLink->id,
        ]);
        $this->assertDatabaseHas('attendance_batch_material_link', [
            'attendance_batch_id' => $batch->id,
            'material_link_id' => $materialLink->id,
        ]);
        $this->assertDatabaseHas('attendance_batch_material_link', [
            'attendance_batch_id' => $batch->id,
            'material_link_id' => $secondMaterialLink->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'attendance_batch_id' => $batch->id,
            'student_id' => $student->id,
            'material_link_id' => $materialLink->id,
        ]);
        $this->assertDatabaseHas('attendance_material_link', [
            'attendance_id' => $batch->attendances()->firstOrFail()->id,
            'material_link_id' => $secondMaterialLink->id,
        ]);
    }
}
