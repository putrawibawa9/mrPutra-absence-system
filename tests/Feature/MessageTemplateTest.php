<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_templates_page_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('message-templates.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('message-templates.index'))
            ->assertOk()
            ->assertSee('Tagih Pembayaran')
            ->assertSee('Murid Selesai');
    }

    public function test_editing_billing_template_changes_generated_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create(['name' => 'Budi', 'phone' => '0811', 'is_active' => true]);

        $this->actingAs($admin)->put(route('message-templates.update', 'billing'), [
            'body' => "Halo {nama}, mohon lunasi pembayaran ya. {link_bayar}",
        ])->assertRedirect();

        $this->assertDatabaseHas('message_templates', ['key' => 'billing']);

        $message = $student->buildLowSessionReminderMessage(0);
        $this->assertStringContainsString('Halo Budi, mohon lunasi pembayaran ya.', $message);
        $this->assertStringContainsString('/cara-bayar/'.$student->id, $message);
        $this->assertStringNotContainsString('semoga sehat selalu', $message); // teks default hilang
    }

    public function test_reset_restores_default_template(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        MessageTemplate::query()->create(['key' => 'inactive_followup', 'body' => 'Custom {nama}']);

        $student = Student::query()->create(['name' => 'Sari', 'phone' => '0812', 'is_active' => true]);
        $this->assertStringContainsString('Custom Sari', $student->buildAttendanceCheckInMessage());

        $this->actingAs($admin)->post(route('message-templates.reset', 'inactive_followup'))->assertRedirect();

        $this->assertDatabaseMissing('message_templates', ['key' => 'inactive_followup']);
        $this->assertStringContainsString('belum hadir di beberapa pertemuan', $student->fresh()->buildAttendanceCheckInMessage());
    }

    public function test_completed_message_renders_with_name_and_feedback_link(): void
    {
        $student = Student::query()->create(['name' => 'Rani', 'phone' => '0813', 'is_active' => true]);

        $message = $student->buildCompletedMessage();
        $this->assertStringContainsString('Rani', $message);
        $this->assertStringContainsString('/feedback/'.$student->id, $message);
        $this->assertStringContainsString('wa.me/62813', $student->completedWhatsAppUrl());
    }

    public function test_unknown_template_key_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->put(route('message-templates.update', 'nope'), ['body' => 'x'])->assertNotFound();
    }
}
