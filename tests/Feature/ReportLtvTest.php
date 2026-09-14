<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportLtvTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPayment(Student $student, int $amount, string $date): void
    {
        Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'price_amount' => $amount,
            'amount_paid' => $amount,
            'payment_date' => $date,
        ]);
    }

    public function test_ltv_report_counts_payment_cycles_and_totals(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $loyal = Student::query()->create(['name' => 'Murid Loyal', 'phone' => '0811', 'is_active' => true]);
        $this->tokenPayment($loyal, 800000, '2026-01-05');
        $this->tokenPayment($loyal, 800000, '2026-03-05');
        $this->tokenPayment($loyal, 800000, '2026-05-05'); // 3 siklus, LTV 2.4jt, 24 token

        $once = Student::query()->create(['name' => 'Murid Sekali', 'phone' => '0812', 'is_active' => true]);
        $this->tokenPayment($once, 500000, '2026-02-01'); // 1 siklus

        $never = Student::query()->create(['name' => 'Murid Belum Bayar', 'phone' => '0813', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('reports.ltv'));

        $response->assertOk();
        $response->assertViewHas('payersCount', 2);
        $response->assertViewHas('repeatCount', 1); // hanya Murid Loyal yang >= 2x
        $response->assertViewHas('totalLtv', 2900000);
        $response->assertViewHas('students', function ($students) use ($loyal, $never) {
            $top = $students->first();

            return $top->id === $loyal->id
                && (int) $top->token_payment_count === 3
                && (int) $top->tokens_purchased === 24
                && (int) $top->ltv_amount === 2400000
                && ! $students->pluck('id')->contains($never->id);
        });
        $response->assertSee('Murid Loyal');
        $response->assertDontSee('Murid Belum Bayar');
    }

    public function test_ltv_report_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('reports.ltv'))->assertForbidden();
    }
}
