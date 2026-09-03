<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $name, string $phone): Student
    {
        return Student::query()->create(['name' => $name, 'phone' => $phone, 'is_active' => true]);
    }

    private function tokenPayment(Student $student, int $sessions = 5, ?string $division = null, ?string $format = null): Payment
    {
        return Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'division' => $division,
            'format' => $format,
            'learning_mode' => ($division && $format) ? Classroom::defaultLearningMode($division, $format) : null,
            'total_sessions' => $sessions,
            'remaining_sessions' => $sessions,
            'price_amount' => 1000000,
            'amount_paid' => 1000000,
            'payment_date' => now()->toDateString(),
        ]);
    }

    private function classroom(string $division, string $format, array $studentIds, array $attributes = []): Classroom
    {
        $classroom = Classroom::query()->create(array_merge([
            'name' => Classroom::makeName($division, $format, Classroom::AGE_KIDS),
            'division' => $division,
            'format' => $format,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => true,
        ], $attributes));
        $classroom->students()->sync($studentIds);

        return $classroom;
    }

    public function test_payment_creation_generates_available_token_ledger(): void
    {
        $student = $this->student('Ledger', '0810');
        $payment = $this->tokenPayment($student, 5);

        $this->assertSame(5, $payment->tokens()->count());
        $this->assertSame(5, $payment->tokens()->where('status', Token::STATUS_AVAILABLE)->count());
    }

    public function test_synchronous_absent_student_forfeits_token(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $present = $this->student('Present', '0811');
        $absent = $this->student('Absent', '0812');
        $payPresent = $this->tokenPayment($present, 5);
        $payAbsent = $this->tokenPayment($absent, 5);

        // English + Semi => synchronous (auto learning_mode).
        $classroom = $this->classroom(Classroom::DIVISION_ENGLISH, Classroom::FORMAT_SEMI, [$present->id, $absent->id]);
        $this->assertTrue($classroom->isSynchronous());

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$present->id], // absent is intentionally omitted
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        // Present student: one token consumed.
        $this->assertSame(4, $payPresent->fresh()->remaining_sessions);
        $this->assertSame(1, $payPresent->tokens()->where('status', Token::STATUS_CONSUMED)->count());

        // Absent student: live session happened, so one token is forfeited.
        $this->assertSame(4, $payAbsent->fresh()->remaining_sessions);
        $this->assertSame(1, $payAbsent->tokens()->where('status', Token::STATUS_FORFEITED)->count());
        $this->assertSame(0, Attendance::query()->where('student_id', $absent->id)->count());
    }

    public function test_self_paced_absent_student_also_forfeits_token(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $present = $this->student('Present', '0811');
        $absent = $this->student('Absent', '0812');
        $payPresent = $this->tokenPayment($present, 5);
        $payAbsent = $this->tokenPayment($absent, 5);

        // Coding Semi => self-paced.
        $classroom = $this->classroom(Classroom::DIVISION_CODING, Classroom::FORMAT_SEMI, [$present->id, $absent->id]);
        $this->assertTrue($classroom->isSelfPaced());

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$present->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        $this->assertSame(4, $payPresent->fresh()->remaining_sessions);
        // Sesi grup tetap terjadi: murid absen kehilangan 1 token (forfeited),
        // berlaku juga untuk kelas self-paced.
        $this->assertSame(4, $payAbsent->fresh()->remaining_sessions);
        $this->assertSame(1, $payAbsent->tokens()->where('status', Token::STATUS_FORFEITED)->count());
        // Tidak ada baris Attendance untuk murid yang absen.
        $this->assertSame(0, Attendance::query()->where('student_id', $absent->id)->count());
    }

    public function test_cancelled_session_does_not_deduct_any_token(): void
    {
        $a = $this->student('A', '0811');
        $b = $this->student('B', '0812');
        $payA = $this->tokenPayment($a, 5);
        $payB = $this->tokenPayment($b, 5);
        $this->classroom(Classroom::DIVISION_ENGLISH, Classroom::FORMAT_SEMI, [$a->id, $b->id]);

        // A cancelled session simply means attendance is never recorded.
        $this->assertSame(5, $payA->fresh()->remaining_sessions);
        $this->assertSame(5, $payB->fresh()->remaining_sessions);
        $this->assertSame(0, Token::query()->where('status', '!=', Token::STATUS_AVAILABLE)->count());
    }

    public function test_self_paced_token_expires_after_validity_window(): void
    {
        config()->set('tokens.validity_period_days', 90);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = $this->student('Solo', '0811');
        $payment = $this->tokenPayment($student, 5, Classroom::DIVISION_CODING, Classroom::FORMAT_PRIVATE);
        $classroom = $this->classroom(Classroom::DIVISION_CODING, Classroom::FORMAT_PRIVATE, [$student->id]);

        // First session 100 days ago establishes the expiry window.
        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->subDays(100)->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$student->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        $this->assertSame(4, $payment->fresh()->remaining_sessions);
        $this->assertNotNull($payment->tokens()->whereNotNull('expires_at')->first());

        $this->artisan('tokens:expire')->assertSuccessful();

        // The 4 leftover available tokens are now past their window.
        $this->assertSame(0, $payment->fresh()->remaining_sessions);
        $this->assertSame(4, $payment->tokens()->where('status', Token::STATUS_EXPIRED)->count());
        $this->assertSame(1, $payment->tokens()->where('status', Token::STATUS_CONSUMED)->count());
    }

    public function test_consume_guard_skips_expired_tokens(): void
    {
        $student = $this->student('Solo', '0811');
        $payment = $this->tokenPayment($student, 1, Classroom::DIVISION_CODING, Classroom::FORMAT_PRIVATE);

        // Force the single token to be already expired.
        $payment->tokens()->update(['expires_at' => now()->subDay()]);
        $this->artisan('tokens:expire')->assertSuccessful();
        $this->assertSame(0, $payment->fresh()->remaining_sessions);

        // resolvePayment requires remaining > 0, so an attendance falls to debt.
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $classroom = $this->classroom(Classroom::DIVISION_CODING, Classroom::FORMAT_PRIVATE, [$student->id]);

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$student->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        // Attendance recorded as token debt; expired token untouched.
        $this->assertNull(Attendance::query()->where('student_id', $student->id)->first()->payment_id);
        $this->assertSame(1, $payment->tokens()->where('status', Token::STATUS_EXPIRED)->count());
    }

    public function test_tokens_are_scoped_by_division_and_format(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = $this->student('Scoped', '0811');

        // Only an English Private token paket exists.
        $englishToken = $this->tokenPayment($student, 5, Classroom::DIVISION_ENGLISH, Classroom::FORMAT_PRIVATE);

        // Recording attendance in a Coding class must NOT spend the English token.
        $codingClass = $this->classroom(Classroom::DIVISION_CODING, Classroom::FORMAT_PRIVATE, [$student->id]);

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $codingClass), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$student->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        $this->assertSame(5, $englishToken->fresh()->remaining_sessions); // untouched
        $this->assertNull(Attendance::query()->where('student_id', $student->id)->first()->payment_id); // token debt
    }

    public function test_editing_attendance_does_not_double_deduct(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = $this->student('Solo', '0811');
        $payment = $this->tokenPayment($student, 5);

        $this->actingAs($teacher)->post(route('attendances.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 60,
            'learning_journal' => 'First.',
        ])->assertRedirect(route('attendances.index', absolute: false));

        $this->assertSame(4, $payment->fresh()->remaining_sessions);
        $attendance = Attendance::query()->firstOrFail();

        // Re-saving the same attendance with the same payment must not deduct again.
        $this->actingAs($teacher)->put(route('attendances.update', $attendance), [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_ids' => [$teacher->id],
            'date' => now()->toDateString(),
            'teaching_minutes' => 90,
            'learning_journal' => 'Edited.',
        ])->assertRedirect(route('attendances.index', absolute: false));

        $this->assertSame(4, $payment->fresh()->remaining_sessions);
        $this->assertSame(1, $payment->tokens()->where('status', Token::STATUS_CONSUMED)->count());
    }

    public function test_backfill_command_seeds_tokens_for_legacy_payments(): void
    {
        $student = $this->student('Legacy', '0811');
        $payment = $this->tokenPayment($student, 5);

        // Simulate a pre-feature payment: wipe its generated ledger.
        $payment->tokens()->delete();
        $this->assertSame(0, $payment->tokens()->count());

        $this->artisan('tokens:backfill')->assertSuccessful();

        $this->assertSame(5, $payment->tokens()->where('status', Token::STATUS_AVAILABLE)->count());
    }
}
