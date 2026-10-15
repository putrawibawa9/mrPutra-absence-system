<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentInfoPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_info_page_requires_valid_signature(): void
    {
        $student = Student::query()->create(['name' => 'Budi', 'phone' => '0811', 'is_active' => true]);

        $this->get(route('payment-info.show', $student))->assertForbidden();
    }

    public function test_signed_pay_info_page_shows_amount_and_accounts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create(['name' => 'Budi', 'phone' => '0811', 'is_active' => true]);
        Payment::query()->create([
            'receipt_number' => 'KWT-PAYINFO',
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 8,
            'remaining_sessions' => 0,
            'price_amount' => 1200000,
            'amount_paid' => 1200000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $response = $this->get(URL::signedRoute('payment-info.show', ['student' => $student]));

        $response->assertOk();
        $response->assertSee('Budi');
        $response->assertSee('Rp 1.200.000');
        $response->assertSee('8 kali pertemuan');
        $response->assertSee('10180276877');
        $response->assertSee('081501035456538');
        // QRIS belum diunggah di lingkungan test → fallback.
        $response->assertSee('QRIS belum tersedia');
    }

    public function test_reminder_message_contains_signed_pay_info_link(): void
    {
        $student = Student::query()->create(['name' => 'Budi', 'phone' => '0811', 'is_active' => true]);

        $message = $student->buildLowSessionReminderMessage(0);

        $this->assertStringContainsString('Untuk bayar via QRIS', $message);
        $this->assertStringContainsString('/cara-bayar/'.$student->id, $message);
        $this->assertStringContainsString('signature=', $message);
    }
}
