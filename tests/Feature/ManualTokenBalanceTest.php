<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ManualTokenBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_manual_opening_balance_for_student(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->update([
            'signature_path' => 'signatures/users/admin-signature.png',
        ]);
        Storage::disk('public')->put('signatures/users/admin-signature.png', 'fake-signature-content');
        $student = Student::query()->create([
            'name' => 'Migrated Student',
            'phone' => '0812340000',
            'email' => 'migrated@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'manual_total_sessions' => 20,
            'manual_remaining_sessions' => 7,
            'manual_price' => 900000,
            'payment_date' => now()->toDateString(),
            'notes' => 'Imported from Excel opening balance',
        ]);

        $payment = Payment::query()->first();

        $this->assertNotNull($payment);
        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame($admin->id, $payment->signed_by_user_id);
        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 20,
            'remaining_sessions' => 7,
            'price_amount' => 900000,
            'amount_paid' => 900000,
            'notes' => 'Imported from Excel opening balance',
        ]);
        $this->assertNull($payment->signature_path);
    }

    public function test_admin_can_open_receipt_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Receipt Student',
            'phone' => '0810000000',
            'email' => 'receipt@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-20260408-ABC123',
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 10,
            'remaining_sessions' => 4,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('payments.receipt', $payment));

        $response->assertOk();
        $response->assertSee('KWT-20260408-ABC123');
        $response->assertSee('Receipt Student');
    }

    public function test_payment_create_page_shows_search_based_student_picker(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Student::query()->create([
            'name' => 'Searchable Payment Student',
            'phone' => '0810000999',
            'email' => 'searchable-payment@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('payments.create'));

        $response->assertOk();
        $response->assertSee('Search by student name, phone, or email');
        $response->assertDontSee('Select student');
    }

    public function test_new_payment_automatically_settles_existing_token_debt(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Debt Payment Student',
            'phone' => '0812222222',
            'email' => 'debtpayment@example.com',
            'is_active' => true,
        ]);
        $package = Package::query()->create([
            'name' => '5 Sessions',
            'total_sessions' => 5,
            'price' => 300000,
        ]);

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->subDay()->toDateString(),
            'learning_journal' => 'Debt lesson one.',
        ]);
        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->toDateString(),
            'learning_journal' => 'Debt lesson two.',
        ]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'package_id' => $package->id,
            'payment_date' => now()->toDateString(),
        ]);

        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect(route('payments.receipt', $payment, absolute: false));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'total_sessions' => 5,
            'remaining_sessions' => 3,
        ]);
        $this->assertSame(0, Attendance::query()->where('student_id', $student->id)->whereNull('payment_id')->count());
        $this->assertSame(2, Attendance::query()->where('payment_id', $payment->id)->count());
    }

    public function test_receipt_uses_signer_profile_signature_when_payment_has_no_signature(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'signature_path' => 'signatures/users/admin-signature.png',
        ]);
        Storage::disk('public')->put('signatures/users/admin-signature.png', 'fake-signature-content');

        $student = Student::query()->create([
            'name' => 'Signature Student',
            'phone' => '0810000001',
            'email' => 'signature-student@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-20260414-SIGN01',
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 10,
            'remaining_sessions' => 4,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
            'signature_path' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('payments.receipt', $payment));

        $response->assertOk();
        $response->assertSee(Storage::disk('public')->url('signatures/users/admin-signature.png'));
    }

    public function test_manual_opening_balance_price_is_shown_on_receipt(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Manual Price Student',
            'phone' => '0810000002',
            'email' => 'manual-price@example.com',
            'is_active' => true,
        ]);

        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-20260414-MANUAL1',
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 12,
            'remaining_sessions' => 5,
            'price_amount' => 850000,
            'amount_paid' => 850000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('payments.receipt', $payment));

        $response->assertOk();
        $response->assertSee('Rp 850.000');
    }

    public function test_admin_receipt_page_shows_whatsapp_send_link(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'WhatsApp Student',
            'phone' => '081234567890',
            'email' => 'wa-student@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-20260414-WA001',
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 8,
            'remaining_sessions' => 4,
            'price_amount' => 400000,
            'amount_paid' => 400000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('payments.receipt', $payment));

        $response->assertOk();
        $response->assertSee('Send Receipt');
        $response->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_signed_public_receipt_can_be_opened_without_login(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Public Receipt Student',
            'phone' => '081234500001',
            'email' => 'public-receipt@example.com',
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'KWT-20260414-PUBLIC',
            'student_id' => $student->id,
            'package_id' => null,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 6,
            'remaining_sessions' => 3,
            'price_amount' => 300000,
            'amount_paid' => 300000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $signedUrl = URL::signedRoute('payments.public-receipt', ['payment' => $payment]);

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertSee('KWT-20260414-PUBLIC');
        $response->assertSee('Public Receipt Student');
        $response->assertDontSee('Back to Payments');
    }
}
