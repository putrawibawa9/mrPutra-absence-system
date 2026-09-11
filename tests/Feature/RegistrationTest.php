<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function pending(array $overrides = []): Registration
    {
        return Registration::query()->create(array_merge([
            'student_name' => 'Budi',
            'phone' => '08123456789',
            'program' => 'english',
            'status' => Registration::STATUS_PENDING,
        ], $overrides));
    }

    public function test_public_can_submit_registration(): void
    {
        $this->post(route('registrations.store'), [
            'student_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'age' => 10,
            'format_preference' => 'private',
            'goal' => 'conversation',
            'available_days' => ['monday', 'wednesday'],
            'time_preferences' => ['sore'],
        ])->assertOk()->assertSee('Terima kasih');

        $this->assertDatabaseHas('registrations', [
            'student_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'program' => 'english', // form khusus English
            'goal' => 'conversation',
            'status' => Registration::STATUS_PENDING,
        ]);

        $reg = Registration::query()->firstOrFail();
        $this->assertSame(['monday', 'wednesday'], $reg->available_days);
        $this->assertSame(['sore'], $reg->time_preferences);
        // Form publik tidak langsung membuat murid.
        $this->assertDatabaseCount('students', 0);
    }

    public function test_registration_validates_required_fields(): void
    {
        $this->from(route('registrations.create'))
            ->post(route('registrations.store'), ['student_name' => '', 'phone' => ''])
            ->assertSessionHasErrors(['student_name', 'phone', 'age', 'format_preference', 'goal', 'available_days', 'time_preferences']);

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_registration_list_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->pending(['student_name' => 'Calon Murid A']);

        $this->actingAs($admin)->get(route('registrations.index'))
            ->assertOk()
            ->assertSee('Pendaftaran Murid')
            ->assertSee('Calon Murid A');

        $this->actingAs($teacher)->get(route('registrations.index'))->assertForbidden();
    }

    public function test_admin_accept_creates_active_student_once(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $reg = $this->pending(['student_name' => 'Budi', 'phone' => '0812', 'email' => 'budi@x.com', 'program' => 'coding']);

        $this->actingAs($admin)->post(route('registrations.accept', $reg))->assertRedirect();

        $reg->refresh();
        $this->assertSame(Registration::STATUS_ACCEPTED, $reg->status);
        $this->assertNotNull($reg->student_id);
        $this->assertDatabaseHas('students', [
            'name' => 'Budi',
            'phone' => '0812',
            'program_type' => Student::PROGRAM_CODING,
            'is_active' => true,
        ]);

        // Terima lagi tidak membuat murid kedua.
        $count = Student::query()->count();
        $this->actingAs($admin)->post(route('registrations.accept', $reg));
        $this->assertSame($count, Student::query()->count());
    }

    public function test_admin_reject_marks_rejected_without_creating_student(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $reg = $this->pending();

        $this->actingAs($admin)->post(route('registrations.reject', $reg))->assertRedirect();

        $this->assertSame(Registration::STATUS_REJECTED, $reg->fresh()->status);
        $this->assertDatabaseCount('students', 0);
    }

    public function test_accept_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $reg = $this->pending();

        $this->actingAs($teacher)->post(route('registrations.accept', $reg))->assertForbidden();
        $this->assertDatabaseCount('students', 0);
    }
}
