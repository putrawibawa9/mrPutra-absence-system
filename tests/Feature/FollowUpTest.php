<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPayment(Student $student): Payment
    {
        return Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => 5,
            'price_amount' => 1000000,
            'amount_paid' => 1000000,
            'payment_date' => now()->toDateString(),
        ]);
    }

    private function forfeit(Student $student, Payment $payment, string $date): void
    {
        Token::query()->create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'status' => Token::STATUS_FORFEITED,
            'forfeited_at' => $date.' 12:00:00',
        ]);
    }

    private function present(Student $student, Payment $payment, User $teacher, string $date): void
    {
        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => $date,
            'learning_journal' => 'Hadir.',
        ]);
    }

    public function test_absent_page_lists_only_students_with_three_consecutive_absences(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        // Andi: 3x absen berturut-turut, belum kembali -> masuk daftar.
        $andi = Student::query()->create(['name' => 'Andi Menghilang', 'phone' => '0811', 'is_active' => true]);
        $payAndi = $this->tokenPayment($andi);
        $this->forfeit($andi, $payAndi, '2026-09-01');
        $this->forfeit($andi, $payAndi, '2026-09-03');
        $this->forfeit($andi, $payAndi, '2026-09-05');

        // Budi: sempat absen tapi sudah kembali hadir -> streak reset, tidak masuk.
        $budi = Student::query()->create(['name' => 'Budi Kembali', 'phone' => '0812', 'is_active' => true]);
        $payBudi = $this->tokenPayment($budi);
        $this->forfeit($budi, $payBudi, '2026-09-01');
        $this->forfeit($budi, $payBudi, '2026-09-02');
        $this->forfeit($budi, $payBudi, '2026-09-03');
        $this->present($budi, $payBudi, $teacher, '2026-09-06');

        // Cici: hanya 2x absen -> di bawah ambang, tidak masuk.
        $cici = Student::query()->create(['name' => 'Cici Duakali', 'phone' => '0813', 'is_active' => true]);
        $payCici = $this->tokenPayment($cici);
        $this->forfeit($cici, $payCici, '2026-09-01');
        $this->forfeit($cici, $payCici, '2026-09-03');

        $response = $this->actingAs($admin)->get(route('follow-up.absent'));

        $response->assertOk();
        $response->assertSee('Andi Menghilang');
        $response->assertSee('3x tidak hadir');
        $response->assertSee('wa.me');
        $response->assertDontSee('Budi Kembali');
        $response->assertDontSee('Cici Duakali');
    }

    public function test_inactive_page_lists_deactivated_students_with_feedback_link(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $alumni = Student::query()->create(['name' => 'Dewi Alumni', 'phone' => '0821', 'is_active' => false, 'deactivated_at' => now()]);
        $active = Student::query()->create(['name' => 'Eka Aktif', 'phone' => '0822', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('follow-up.inactive'));

        $response->assertOk();
        $response->assertSee('Dewi Alumni');
        $response->assertSee('Minta Feedback via WA');
        $response->assertSee('wa.me');
        $response->assertDontSee('Eka Aktif');
    }

    public function test_follow_up_pages_are_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->get(route('follow-up.absent'))->assertForbidden();
        $this->actingAs($teacher)->get(route('follow-up.inactive'))->assertForbidden();
    }
}
