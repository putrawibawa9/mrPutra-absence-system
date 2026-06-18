<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LearningModule;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_module_payment_from_saved_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Module Buyer',
            'phone' => '0812000001',
            'email' => 'modulebuyer@example.com',
            'is_active' => true,
        ]);
        $learningModule = LearningModule::query()->create([
            'name' => 'Module TOEFL Basics',
            'price' => 175000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_BOOK,
            'learning_module_id' => $learningModule->id,
            'initial_paid_amount' => 50000,
            'payment_date' => now()->toDateString(),
        ]);

        $payment = Payment::query()->with('installments')->firstOrFail();

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'learning_module_id' => $learningModule->id,
            'book_title' => 'Module TOEFL Basics',
            'price_amount' => 175000,
            'amount_paid' => 50000,
        ]);
        $this->assertDatabaseHas('payment_installments', [
            'payment_id' => $payment->id,
            'amount' => 50000,
        ]);
    }

    public function test_cash_flow_page_shows_expected_metrics_for_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Cash Flow Student',
            'phone' => '0812000002',
            'email' => 'cashflowstudent@example.com',
            'is_active' => true,
        ]);        $module = LearningModule::query()->create([
            'name' => 'Module Public Speaking',
            'price' => 50000,
            'is_active' => true,
        ]);
        $expenseCategory = ExpenseCategory::query()->create([
            'name' => 'Operasional',
            'is_active' => true,
        ]);

        $packagePayment = Payment::query()->create([
            'receipt_number' => 'KWT-CASH-001',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'price_amount' => 800000,
            'amount_paid' => 300000,
            'payment_date' => '2026-04-10',
            'signed_by_user_id' => $admin->id,
        ]);
        $packagePayment->installments()->create([
            'amount' => 300000,
            'payment_date' => '2026-04-10',
            'notes' => 'Package installment',
            'received_by_user_id' => $admin->id,
        ]);

        $modulePayment = Payment::query()->create([
            'receipt_number' => 'KWT-CASH-002',
            'student_id' => $student->id,
            'learning_module_id' => $module->id,
            'book_title' => $module->name,
            'source_type' => Payment::SOURCE_BOOK,
            'total_sessions' => 0,
            'remaining_sessions' => 0,
            'price_amount' => 50000,
            'amount_paid' => 50000,
            'payment_date' => '2026-04-12',
            'signed_by_user_id' => $admin->id,
        ]);
        $modulePayment->installments()->create([
            'amount' => 50000,
            'payment_date' => '2026-04-12',
            'notes' => 'Module payment',
            'received_by_user_id' => $admin->id,
        ]);

        $manualPayment = Payment::query()->create([
            'receipt_number' => 'KWT-CASH-003',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 5,
            'remaining_sessions' => 5,
            'price_amount' => 400000,
            'amount_paid' => 400000,
            'payment_date' => '2026-04-15',
            'signed_by_user_id' => $admin->id,
        ]);

        Expense::query()->create([
            'expense_category_id' => $expenseCategory->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Print Modul',
            'amount' => 100000,
            'expense_date' => '2026-04-13',
            'notes' => 'Biaya cetak modul',
        ]);

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $packagePayment->id,
            'date' => '2026-04-11',
            'learning_journal' => 'Meeting one',
        ]);
        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $packagePayment->id,
            'date' => '2026-04-18',
            'learning_journal' => 'Meeting two',
        ]);

        // Fee guru otomatis untuk kedua pertemuan (Rp 40.000 per pertemuan).
        $feeCategory = ExpenseCategory::query()->create([
            'name' => 'Fee Guru',
            'is_active' => true,
        ]);
        foreach (['2026-04-11', '2026-04-18'] as $feeDate) {
            Expense::query()->create([
                'expense_category_id' => $feeCategory->id,
                'created_by_user_id' => $admin->id,
                'teacher_user_id' => $teacher->id,
                'title' => 'Fee guru',
                'amount' => 40000,
                'expense_date' => $feeDate,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('cash-flow.index', [
            'date_from' => '2026-04-01',
            'date_to' => '2026-04-30',
        ]));

        // Akrual: token diakui per sesi (2 x 100.000) + buku 50.000 = 250.000.
        // Saldo awal manual (400.000) TIDAK dihitung sebagai pendapatan.
        $response->assertOk();
        $response->assertViewHas('revenue', 250000);
        $response->assertViewHas('expenseTotal', 180000);
        $response->assertViewHas('teacherFeeTotal', 80000);
        $response->assertViewHas('netProfit', 70000);
        $response->assertViewHas('averageRevenuePerMeeting', 100000);
        $response->assertViewHas('meetingCount', 2);
        $response->assertViewHas('incomeBySource', fn (array $incomeBySource) => $incomeBySource['student_payments'] === 200000
            && $incomeBySource['module_payments'] === 50000);
        $response->assertSee('250.000');
        $response->assertSee('70.000');
        $response->assertSee('200.000');
        $response->assertSee('50.000');
        $response->assertSee('80.000');
        $response->assertSee('28,0%');
        $response->assertDontSee('400.000');
    }

    public function test_cash_flow_separates_private_and_group_session_margin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $makeTokenPayment = function (Student $student) {
            return Payment::query()->create([
                'student_id' => $student->id,
                'source_type' => Payment::SOURCE_TOKEN,
                'total_sessions' => 8,
                'remaining_sessions' => 7,
                'price_amount' => 800000, // 100.000 / sesi
                'amount_paid' => 800000,
                'payment_date' => '2026-04-01',
            ]);
        };

        $studentA = Student::query()->create(['name' => 'Private A', 'phone' => '0812000010', 'is_active' => true]);
        $studentB = Student::query()->create(['name' => 'Group B', 'phone' => '0812000011', 'is_active' => true]);
        $studentC = Student::query()->create(['name' => 'Group C', 'phone' => '0812000012', 'is_active' => true]);

        // Private: 1 sesi 1-on-1
        $privateAttendance = Attendance::query()->create([
            'student_id' => $studentA->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $makeTokenPayment($studentA)->id,
            'date' => '2026-04-05',
            'learning_journal' => 'Private lesson.',
        ]);

        // Grup: 1 batch, 2 peserta
        $batch = AttendanceBatch::query()->create([
            'title' => 'Group Class',
            'teacher_id' => $teacher->id,
            'date' => '2026-04-06',
        ]);
        Attendance::query()->create([
            'student_id' => $studentB->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $makeTokenPayment($studentB)->id,
            'attendance_batch_id' => $batch->id,
            'date' => '2026-04-06',
        ]);
        Attendance::query()->create([
            'student_id' => $studentC->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $makeTokenPayment($studentC)->id,
            'attendance_batch_id' => $batch->id,
            'date' => '2026-04-06',
        ]);

        // Fee guru: private tertaut attendance_id, grup tertaut attendance_batch_id (1x per batch)
        $feeCategory = ExpenseCategory::query()->create(['name' => 'Fee Guru', 'is_active' => true]);
        Expense::query()->create([
            'expense_category_id' => $feeCategory->id,
            'created_by_user_id' => $admin->id,
            'teacher_user_id' => $teacher->id,
            'attendance_id' => $privateAttendance->id,
            'title' => 'Fee guru private',
            'amount' => 40000,
            'expense_date' => '2026-04-05',
        ]);
        Expense::query()->create([
            'expense_category_id' => $feeCategory->id,
            'created_by_user_id' => $admin->id,
            'teacher_user_id' => $teacher->id,
            'attendance_batch_id' => $batch->id,
            'title' => 'Fee guru grup',
            'amount' => 40000,
            'expense_date' => '2026-04-06',
        ]);

        $response = $this->actingAs($admin)->get(route('cash-flow.index', [
            'date_from' => '2026-04-01',
            'date_to' => '2026-04-30',
        ]));

        $response->assertOk();
        $response->assertViewHas('sessionTypeBreakdown', function (array $breakdown) {
            $private = $breakdown['private'];
            $group = $breakdown['group'];

            return $private['revenue'] === 100000
                && $private['fee'] === 40000
                && $private['margin'] === 60000
                && $private['sessions'] === 1
                && $private['avg_margin'] === 60000
                && $group['revenue'] === 200000 // 2 peserta x 100.000
                && $group['fee'] === 40000      // 1 fee guru untuk 1 pertemuan grup
                && $group['margin'] === 160000
                && $group['sessions'] === 1
                && $group['participants'] === 2
                && $group['avg_margin'] === 160000
                && (float) $group['avg_participants'] === 2.0;
        });

        // Net per sesi: pendapatan - fee guru, grup digabung per batch.
        $response->assertViewHas('sessionNetEntries', function ($entries) {
            $entries = collect($entries);
            $groupNet = $entries->first(fn ($entry) => str_starts_with($entry->id, 'net-batch-'));
            $privateNet = $entries->first(fn ($entry) => str_starts_with($entry->id, 'net-') && ! str_starts_with($entry->id, 'net-batch-'));

            return $groupNet && (int) $groupNet->gross === 200000 && (int) $groupNet->fee === 40000 && (int) $groupNet->net === 160000
                && $privateNet && (int) $privateNet->gross === 100000 && (int) $privateNet->fee === 40000 && (int) $privateNet->net === 60000;
        });

        // Expense entries menyembunyikan fee guru (di test ini semua expense adalah fee guru -> kosong).
        $response->assertViewHas('expenses', fn ($expenses) => collect($expenses)
            ->every(fn ($expense) => $expense->category?->name !== 'Fee Guru'));
    }
}
