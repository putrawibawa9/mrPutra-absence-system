<?php

namespace Tests\Feature;

use App\Models\Package;
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
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
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

    public function test_attendance_cannot_be_recorded_when_payment_has_no_remaining_sessions(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student Two',
            'phone' => '08987654321',
            'email' => 'student2@example.com',
        ]);
        $package = Package::query()->create([
            'name' => '5 Sessions',
            'total_sessions' => 5,
            'price' => 300000,
        ]);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
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

    public function test_create_attendance_page_shows_previous_learning_journal_for_selected_student(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student Journal',
            'phone' => '0812345000',
            'email' => 'journal@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        Payment::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $student->attendances()->create([
            'teacher_id' => $teacher->id,
            'payment_id' => Payment::query()->first()->id,
            'date' => now()->subWeek()->toDateString(),
            'learning_journal' => 'Previous lesson focused on speaking confidence.',
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.create', [
            'student_id' => $student->id,
        ]));

        $response->assertOk();
        $response->assertSee('Previous lesson focused on speaking confidence.');
    }

    public function test_create_attendance_page_shows_student_book_info(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Student Book',
            'phone' => '0812345111',
            'email' => 'studentbook@example.com',
            'book_info' => 'Cambridge Primary 3, Unit 2.',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        Payment::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 5,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.create', [
            'student_id' => $student->id,
        ]));

        $response->assertOk();
        $response->assertSee('Cambridge Primary 3, Unit 2.');
    }

    public function test_create_attendance_page_shows_search_based_student_picker(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        Student::query()->create([
            'name' => 'Search Student',
            'phone' => '0812345222',
            'email' => 'search-student@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.create'));

        $response->assertOk();
        $response->assertSee('Search by student name or phone');
        $response->assertDontSee('Select student');
    }

    public function test_create_attendance_page_shows_combined_active_tokens(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Combined Token Student',
            'phone' => '0812345333',
            'email' => 'combined-token@example.com',
            'is_active' => true,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-COMB-003',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'payment_date' => now()->subDay()->toDateString(),
        ]);
        Payment::query()->create([
            'receipt_number' => 'KWT-COMB-004',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 1,
            'remaining_sessions' => 1,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->get(route('attendances.create', [
            'student_id' => $student->id,
        ]));

        $response->assertOk();
        $response->assertSee('9 sessions available');
        $response->assertSee('Combined from 2 active payments.');
        $response->assertDontSee('Record as token debt');
    }
}
