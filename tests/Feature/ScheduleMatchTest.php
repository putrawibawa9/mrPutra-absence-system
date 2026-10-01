<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Services\ScheduleMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleMatchTest extends TestCase
{
    use RefreshDatabase;

    private function availability(User $teacher, string $day, string $start, string $end): TeacherAvailability
    {
        return TeacherAvailability::query()->create([
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
    }

    private function teaching(User $teacher, string $day, string $start, string $end): void
    {
        TeacherSchedule::query()->create([
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'title' => 'Kelas',
            'is_active' => true,
        ]);
    }

    public function test_teacher_already_teaching_at_that_time_is_not_available(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->availability($teacher, 'monday', '09:00', '11:00'); // pagi
        $this->teaching($teacher, 'monday', '09:00', '11:00');      // sudah ngajar penuh

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['monday'], ['pagi']);

        $this->assertCount(0, $matches);
    }

    public function test_co_teaching_also_makes_teacher_unavailable(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $primary = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->availability($teacher, 'monday', '09:00', '11:00'); // pagi

        // $teacher jadi co-teacher penuh di jam itu.
        TeacherSchedule::query()->create([
            'teacher_id' => $primary->id,
            'co_teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'title' => 'Kelas',
            'is_active' => true,
        ]);

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['monday'], ['pagi']);

        $this->assertCount(0, $matches);
    }

    public function test_only_the_free_remainder_is_offered(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->availability($teacher, 'monday', '06:00', '11:00'); // pagi
        $this->teaching($teacher, 'monday', '09:00', '11:00');      // sisa bebas 06:00-09:00

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['monday'], ['pagi']);

        $this->assertCount(1, $matches);
        $this->assertSame('06:00 - 09:00', $matches->first()->slots[0]->time_label);
    }

    public function test_matches_teacher_when_day_and_time_bucket_overlap(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Bu Guru']);
        $this->availability($teacher, 'monday', '09:00', '11:00'); // pagi

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['monday'], ['pagi']);

        $this->assertCount(1, $matches);
        $this->assertSame($teacher->id, $matches->first()->teacher_id);
        $this->assertSame('Senin 09:00 - 11:00', $matches->first()->slots[0]->day_label.' '.$matches->first()->slots[0]->time_label);
    }

    public function test_no_match_when_day_differs(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->availability($teacher, 'monday', '09:00', '11:00');

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['tuesday'], ['pagi']);

        $this->assertCount(0, $matches);
    }

    public function test_no_match_when_time_bucket_differs(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->availability($teacher, 'monday', '09:00', '11:00');

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['monday'], ['malam']);

        $this->assertCount(0, $matches);
    }

    public function test_empty_day_preference_is_treated_as_any_day(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->availability($teacher, 'saturday', '19:00', '21:00'); // malam

        $matches = app(ScheduleMatchService::class)->matchForPreferences([], ['malam']);

        $this->assertCount(1, $matches);
    }

    public function test_inactive_or_unavailable_slots_are_ignored(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        TeacherAvailability::query()->create(['teacher_id' => $teacher->id, 'day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '11:00', 'status' => TeacherAvailability::STATUS_UNAVAILABLE, 'is_active' => true]);
        TeacherAvailability::query()->create(['teacher_id' => $teacher->id, 'day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '11:00', 'status' => TeacherAvailability::STATUS_AVAILABLE, 'is_active' => false]);

        $matches = app(ScheduleMatchService::class)->matchForPreferences(['monday'], ['pagi']);

        $this->assertCount(0, $matches);
    }

    public function test_match_page_is_admin_only_and_lists_matches(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Pak Match']);
        $this->availability($teacher, 'monday', '09:00', '11:00');

        Registration::query()->create([
            'student_name' => 'Calon Murid',
            'phone' => '0812',
            'program' => 'english',
            'format_preference' => 'private',
            'available_days' => ['monday'],
            'time_preferences' => ['pagi'],
            'status' => Registration::STATUS_PENDING,
        ]);

        $teacherUser = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacherUser)->get(route('schedule-match.index'))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('schedule-match.index'));
        $response->assertOk();
        $response->assertViewHas('matchedCount', 1);
        $response->assertSee('Calon Murid');
        $response->assertSee('Pak Match');
    }
}
