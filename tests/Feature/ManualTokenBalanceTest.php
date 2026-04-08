<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualTokenBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_manual_opening_balance_for_student(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
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
            'payment_date' => now()->toDateString(),
            'notes' => 'Imported from Excel opening balance',
            'signature' => UploadedFile::fake()->image('signature.png'),
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
            'notes' => 'Imported from Excel opening balance',
        ]);
        Storage::disk('public')->assertExists($payment->signature_path);
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
}
