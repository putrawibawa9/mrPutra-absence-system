<?php

namespace Tests\Feature;

use App\Models\MaterialLink;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_record_attendance_and_consume_one_session(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student One',
            'phone' => '08123456789',
            'email' => 'student@example.com',
        ]);        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'total_sessions' => 10,
            'remaining_sessions' => 2,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 90,
            'learning_journal' => 'Reviewed basic grammar and assigned homework.',
            'notes' => 'Present',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'teaching_minutes' => 90,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'remaining_sessions' => 1,
        ]);
    }

    public function test_teacher_can_record_attendance_with_long_homework_content(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Homework Student',
            'phone' => '08123456781',
            'email' => 'homework@example.com',
        ]);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 6,
            'remaining_sessions' => 6,
            'payment_date' => now()->toDateString(),
        ]);

        $homeworkContent = rtrim(str_repeat("Practice these chunks in full sentences.\n", 40));

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 75,
            'learning_journal' => 'Speaking drills and listening response practice.',
            'homework_content' => $homeworkContent,
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'homework_content' => $homeworkContent,
        ]);
    }

    public function test_teacher_can_record_attendance_with_optional_material_links(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Material Link Student',
            'phone' => '08123456782',
            'email' => 'materiallink@example.com',
        ]);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 6,
            'remaining_sessions' => 6,
            'payment_date' => now()->toDateString(),
        ]);
        $materialLink = MaterialLink::create([
            'title' => 'Conversation Material',
            'url' => 'https://example.com/conversation-material',
            'is_active' => true,
        ]);
        $secondMaterialLink = MaterialLink::create([
            'title' => 'Review Material',
            'url' => 'https://example.com/review-material',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'material_link_ids' => [$materialLink->id, $secondMaterialLink->id],
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'learning_journal' => 'Material link attendance.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'material_link_id' => $materialLink->id,
        ]);
        $this->assertDatabaseHas('attendance_material_link', [
            'attendance_id' => \App\Models\Attendance::query()->first()->id,
            'material_link_id' => $materialLink->id,
        ]);
        $this->assertDatabaseHas('attendance_material_link', [
            'attendance_id' => \App\Models\Attendance::query()->first()->id,
            'material_link_id' => $secondMaterialLink->id,
        ]);
    }

    public function test_attendance_cannot_be_recorded_when_payment_has_no_remaining_sessions(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '08987654321',
            'email' => 'student2@example.com',
        ]);        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'total_sessions' => 5,
            'remaining_sessions' => 0,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->from(route('attendances.create', ['student_id' => $student->id]))
            ->actingAs($teacher)
            ->post(route('attendances.store'), [
                'mode' => 'single',
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'teacher_ids' => [$teacher->id],
                'date' => now()->toDateString(),
                'teaching_minutes' => 60,
                'learning_journal' => 'Attempted lesson but no remaining sessions.',
            ]);

        $response->assertRedirect(route('attendances.create', ['student_id' => $student->id], absolute: false));
        $response->assertSessionHasErrors('payment_id');
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'remaining_sessions' => 0,
        ]);
    }

    public function test_teacher_can_record_attendance_as_token_debt_when_student_has_no_tokens(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Debt Student',
            'phone' => '0812345001',
            'email' => 'debt@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 45,
            'learning_journal' => 'Lesson recorded before payment was made.',
            'notes' => 'Token debt',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'teaching_minutes' => 45,
            'notes' => 'Token debt',
        ]);
    }

    public function test_teacher_can_record_attendance_using_combined_active_tokens(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Multi Payment Student',
            'phone' => '0812000001',
            'email' => 'multi-payment@example.com',
            'is_active' => true,
        ]);

        $olderPayment = Payment::query()->create([
            'receipt_number' => 'KWT-COMB-001',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'payment_date' => now()->subDay()->toDateString(),
        ]);
        $newerPayment = Payment::query()->create([
            'receipt_number' => 'KWT-COMB-002',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 1,
            'remaining_sessions' => 1,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 120,
            'learning_journal' => 'Combined token attendance.',
        ]);

        $response->assertRedirect(route('attendances.index', absolute: false));
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $olderPayment->id,
            'teaching_minutes' => 120,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $olderPayment->id,
            'remaining_sessions' => 7,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $newerPayment->id,
            'remaining_sessions' => 1,
        ]);
    }
}
