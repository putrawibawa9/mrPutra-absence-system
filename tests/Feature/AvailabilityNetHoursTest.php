<?php

namespace Tests\Feature;

use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityNetHoursTest extends TestCase
{
    use RefreshDatabase;

    private function availability(User $t, string $day, string $s, string $e): TeacherAvailability
    {
        return TeacherAvailability::query()->create([
            'teacher_id' => $t->id, 'day_of_week' => $day, 'start_time' => $s, 'end_time' => $e,
            'status' => TeacherAvailability::STATUS_AVAILABLE, 'is_active' => true,
        ]);
    }

    private function schedule(User $t, string $day, string $s, string $e): void
    {
        TeacherSchedule::query()->create([
            'teacher_id' => $t->id, 'classroom_id' => null, 'title' => 'Kelas',
            'day_of_week' => $day, 'start_time' => $s, 'end_time' => $e, 'is_active' => true,
        ]);
    }

    private function slotFor(User $t, int $availabilityId)
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $slot = null;
        $this->actingAs($admin)->get(route('teacher-availabilities.index'))
            ->assertOk()
            ->assertViewHas('teacherCards', function ($cards) use ($t, $availabilityId, &$slot) {
                $card = $cards->firstWhere('teacher.id', $t->id);
                $slot = collect($card->slots)->firstWhere('id', $availabilityId);

                return $slot !== null;
            });

        return $slot;
    }

    public function test_booked_teaching_hours_are_subtracted_leaving_free_windows(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $availability = $this->availability($teacher, 'monday', '17:00', '20:00');
        $this->schedule($teacher, 'monday', '18:20', '19:30');

        $slot = $this->slotFor($teacher, $availability->id);

        $this->assertFalse($slot->is_fully_booked);
        $this->assertTrue($slot->has_booked);
        $this->assertSame('17:00 - 18:20, 19:30 - 20:00', $slot->free_label);
        $this->assertSame('18:20 - 19:30', $slot->booked_label);
    }

    public function test_fully_booked_slot_has_no_free_time(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $availability = $this->availability($teacher, 'tuesday', '09:00', '10:10');
        $this->schedule($teacher, 'tuesday', '09:00', '10:10');

        $slot = $this->slotFor($teacher, $availability->id);

        $this->assertTrue($slot->is_fully_booked);
        $this->assertSame('-', $slot->free_label);
    }

    public function test_co_teaching_hours_are_also_subtracted(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $primary = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $availability = $this->availability($teacher, 'monday', '17:00', '20:00');

        // $teacher ditugaskan sebagai CO-TEACHER di jadwal milik $primary.
        TeacherSchedule::query()->create([
            'teacher_id' => $primary->id,
            'co_teacher_id' => $teacher->id,
            'title' => 'Kelas',
            'day_of_week' => 'monday',
            'start_time' => '18:20',
            'end_time' => '19:30',
            'is_active' => true,
        ]);

        $slot = $this->slotFor($teacher, $availability->id);

        $this->assertTrue($slot->has_booked);
        $this->assertSame('17:00 - 18:20, 19:30 - 20:00', $slot->free_label);
        $this->assertSame('18:20 - 19:30', $slot->booked_label);
    }

    public function test_schedule_on_other_day_does_not_affect_slot(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $availability = $this->availability($teacher, 'wednesday', '13:00', '15:00');
        $this->schedule($teacher, 'thursday', '13:00', '15:00'); // hari lain

        $slot = $this->slotFor($teacher, $availability->id);

        $this->assertFalse($slot->has_booked);
        $this->assertSame('13:00 - 15:00', $slot->free_label);
    }
}
