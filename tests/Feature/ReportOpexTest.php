<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportOpexTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPayment(Student $student): Payment
    {
        return Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 8,
            'remaining_sessions' => 7,
            'price_amount' => 800000,
            'amount_paid' => 800000,
            'payment_date' => '2026-09-01',
        ]);
    }

    private function attendedSession(Student $student, Payment $payment, string $date, int $teacherId): void
    {
        Attendance::query()->create([
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'teacher_id' => $teacherId,
            'date' => $date,
        ]);
    }

    public function test_opex_splits_fixed_variable_and_loads_overhead_per_meeting(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $fixed = ExpenseCategory::query()->create(['name' => 'Sewa', 'is_active' => true, 'cost_behavior' => ExpenseCategory::COST_FIXED]);
        $variable = ExpenseCategory::query()->create(['name' => 'Fotokopi', 'is_active' => true, 'cost_behavior' => ExpenseCategory::COST_VARIABLE]);
        $untagged = ExpenseCategory::query()->create(['name' => 'Lain-lain', 'is_active' => true]);

        Expense::query()->create(['expense_category_id' => $fixed->id, 'created_by_user_id' => $admin->id, 'title' => 'Sewa ruko', 'amount' => 2000000, 'expense_date' => '2026-09-03']);
        Expense::query()->create(['expense_category_id' => $variable->id, 'created_by_user_id' => $admin->id, 'title' => 'Fotokopi buku', 'amount' => 300000, 'expense_date' => '2026-09-05']);
        Expense::query()->create(['expense_category_id' => $untagged->id, 'created_by_user_id' => $admin->id, 'title' => 'Misc', 'amount' => 100000, 'expense_date' => '2026-09-06']);

        // 2 pertemuan token (private) pada periode.
        $s1 = Student::query()->create(['name' => 'Andi', 'phone' => '0811', 'is_active' => true]);
        $s2 = Student::query()->create(['name' => 'Budi', 'phone' => '0812', 'is_active' => true]);
        $this->attendedSession($s1, $this->tokenPayment($s1), '2026-09-10', $teacher->id);
        $this->attendedSession($s2, $this->tokenPayment($s2), '2026-09-11', $teacher->id);

        $response = $this->actingAs($admin)->get(route('reports.opex', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']));

        $response->assertOk();
        $response->assertViewHas('fixedTotal', 2000000);
        $response->assertViewHas('variableTotal', 300000);
        $response->assertViewHas('unclassifiedTotal', 100000);
        $response->assertViewHas('opexTotal', 2400000);
        $response->assertViewHas('meetingCount', 2);
        // Overhead tetap 2.000.000 / 2 pertemuan = 1.000.000 per pertemuan.
        $response->assertViewHas('overheadPerMeeting', 1000000);
        // Pendapatan per sesi = 800.000/8 = 100.000; net = 100.000 - 0 fee - 1.000.000 overhead < 0 (bocor).
        $response->assertViewHas('leakCount', 2);
    }

    public function test_opex_report_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('reports.opex'))->assertForbidden();
    }
}
