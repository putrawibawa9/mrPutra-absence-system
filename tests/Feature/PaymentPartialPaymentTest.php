<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPartialPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_partial_package_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Partial Student',
            'phone' => '0815555555',
            'email' => 'partial@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => 'Private Intensive',
            'total_sessions' => 8,
            'price' => 800000,
        ]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'package_id' => $package->id,
            'initial_paid_amount' => 300000,
            'payment_date' => now()->toDateString(),
        ]);

        $payment = Payment::query()->with('installments')->firstOrFail();

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'price_amount' => 800000,
            'amount_paid' => 300000,
        ]);
        $this->assertSame(500000, $payment->outstandingAmount());
        $this->assertCount(1, $payment->installments);
        $this->assertDatabaseHas('payment_installments', [
            'payment_id' => $payment->id,
            'amount' => 300000,
        ]);
    }

    public function test_admin_can_add_installment_to_existing_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Installment Student',
            'phone' => '0816666666',
            'email' => 'installment@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-PARTIAL-001',
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'price_amount' => 800000,
            'amount_paid' => 300000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);
        $payment->installments()->create([
            'amount' => 300000,
            'payment_date' => now()->toDateString(),
            'notes' => 'Initial payment',
            'received_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('payments.installments.store', $payment), [
            'amount' => 200000,
            'payment_date' => now()->addDay()->toDateString(),
            'notes' => 'Second installment',
        ]);

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount_paid' => 500000,
        ]);
        $this->assertDatabaseHas('payment_installments', [
            'payment_id' => $payment->id,
            'amount' => 200000,
            'notes' => 'Second installment',
        ]);
    }

    public function test_installment_settles_existing_token_debt_when_payment_still_has_sessions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Debt Reconcile Student',
            'phone' => '0819999999',
            'email' => 'debtreconcile@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '8 Sessions',
            'total_sessions' => 8,
            'price' => 800000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-PARTIAL-RECON',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'price_amount' => 800000,
            'amount_paid' => 200000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);
        $payment->installments()->create([
            'amount' => 200000,
            'payment_date' => now()->toDateString(),
            'notes' => 'Initial payment',
            'received_by_user_id' => $admin->id,
        ]);
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->toDateString(),
            'learning_journal' => 'Debt attendance waiting for settlement.',
        ]);

        $response = $this->actingAs($admin)->post(route('payments.installments.store', $payment), [
            'amount' => 100000,
            'payment_date' => now()->addDay()->toDateString(),
            'notes' => 'Follow-up installment',
        ]);

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'payment_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'remaining_sessions' => 7,
            'amount_paid' => 300000,
        ]);
    }

    public function test_admin_can_create_partial_book_or_module_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Book Student',
            'phone' => '0817777777',
            'email' => 'bookpayment@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_BOOK,
            'book_title' => 'Module IELTS Speaking',
            'book_price' => 200000,
            'initial_paid_amount' => 50000,
            'payment_date' => now()->toDateString(),
        ]);

        $payment = Payment::query()->with('installments')->firstOrFail();

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'source_type' => Payment::SOURCE_BOOK,
            'book_title' => 'Module IELTS Speaking',
            'total_sessions' => 0,
            'remaining_sessions' => 0,
            'price_amount' => 200000,
            'amount_paid' => 50000,
        ]);
        $this->assertSame(150000, $payment->outstandingAmount());
        $this->assertDatabaseHas('payment_installments', [
            'payment_id' => $payment->id,
            'amount' => 50000,
        ]);
    }

    public function test_admin_can_delete_payment_and_convert_linked_attendances_to_token_debt(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Delete Payment Student',
            'phone' => '0818888888',
            'email' => 'deletepayment@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '10 Sessions',
            'total_sessions' => 10,
            'price' => 500000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-DELETE-001',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'price_amount' => 500000,
            'amount_paid' => 500000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);
        $payment->installments()->create([
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'notes' => 'Full payment',
            'received_by_user_id' => $admin->id,
        ]);
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'Attendance tied to payment.',
        ]);

        $response = $this->actingAs($admin)->delete(route('payments.destroy', $payment));

        $response->assertRedirect(route('payments.index', absolute: false));
        $this->assertDatabaseMissing('payments', [
            'id' => $payment->id,
        ]);
        $this->assertDatabaseMissing('payment_installments', [
            'payment_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'payment_id' => null,
        ]);
    }

    public function test_admin_can_manually_reconcile_debt_to_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Manual Reconcile Student',
            'phone' => '0811212121',
            'email' => 'manualreconcile@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '6 Sessions',
            'total_sessions' => 6,
            'price' => 600000,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-RECON-001',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => 6,
            'remaining_sessions' => 6,
            'price_amount' => 600000,
            'amount_paid' => 600000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);
        $attendance = Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->toDateString(),
            'learning_journal' => 'Needs manual reconcile.',
        ]);

        $response = $this->actingAs($admin)->post(route('payments.reconcile-debt', $payment));

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'payment_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'remaining_sessions' => 5,
        ]);
    }
}
