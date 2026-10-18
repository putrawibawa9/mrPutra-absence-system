<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ScheduleChange;
use App\Models\Student;
use App\Models\TeacherLeave;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Services\ScheduleOccurrenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleCalendarTest extends TestCase
{
    use RefreshDatabase;

    private string $monday = '2026-10-19'; // Senin
    private string $tuesday = '2026-10-20';

    private function makeSchedule(User $teacher): TeacherSchedule
    {
        $student = Student::query()->create(['name' => 'Murid X', 'phone' => '0811', 'is_active' => true]);
        $classroom = Classroom::query()->create([
            'name' => 'English Private',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => true,
        ]);
        $classroom->students()->attach($student);

        return TeacherSchedule::query()->create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);
    }

    public function test_substitute_shows_both_teachers(): void
    {
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Asli']);
        $t2 = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Pengganti']);
        $schedule = $this->makeSchedule($t1);
        ScheduleChange::query()->create([
            'teacher_schedule_id' => $schedule->id,
            'date' => $this->monday,
            'substitute_teacher_id' => $t2->id,
        ]);

        $occ = app(ScheduleOccurrenceService::class)->forDate(Carbon::parse($this->monday))->first();

        $this->assertSame(ScheduleOccurrenceService::STATUS_SUBSTITUTE, $occ->status);
        $this->assertSame($t2->id, $occ->effective_teacher->id);
        $this->assertSame($t1->id, $occ->original_teacher->id);
    }

    public function test_moved_session_appears_on_new_date_and_leaves_original(): void
    {
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $schedule = $this->makeSchedule($t1);
        ScheduleChange::query()->create([
            'teacher_schedule_id' => $schedule->id,
            'date' => $this->monday,
            'moved_to_date' => $this->tuesday,
        ]);

        $range = app(ScheduleOccurrenceService::class)->forRange(Carbon::parse($this->monday), Carbon::parse($this->tuesday));

        $mon = $range->firstWhere(fn ($o) => $o->date->toDateString() === $this->monday);
        $tue = $range->firstWhere(fn ($o) => $o->date->toDateString() === $this->tuesday);
        $this->assertSame(ScheduleOccurrenceService::STATUS_MOVED_OUT, $mon->status);
        $this->assertSame(ScheduleOccurrenceService::STATUS_MOVED_IN, $tue->status);
    }

    public function test_cancelled_session_marked(): void
    {
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $schedule = $this->makeSchedule($t1);
        ScheduleChange::query()->create([
            'teacher_schedule_id' => $schedule->id,
            'date' => $this->monday,
            'is_cancelled' => true,
        ]);

        $occ = app(ScheduleOccurrenceService::class)->forDate(Carbon::parse($this->monday))->first();
        $this->assertSame(ScheduleOccurrenceService::STATUS_CANCELLED, $occ->status);
    }

    public function test_teacher_on_leave_without_substitute_is_flagged(): void
    {
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $schedule = $this->makeSchedule($t1);
        TeacherLeave::query()->create([
            'teacher_id' => $t1->id,
            'date' => $this->monday,
            'status' => TeacherLeave::STATUS_APPROVED,
        ]);

        $occ = app(ScheduleOccurrenceService::class)->forDate(Carbon::parse($this->monday))->first();
        $this->assertSame(ScheduleOccurrenceService::STATUS_LEAVE_UNASSIGNED, $occ->status);
    }

    public function test_calendar_page_renders_week(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->makeSchedule($t1);

        $this->actingAs($admin)->get(route('schedule-calendar.index', ['week' => $this->monday]))
            ->assertOk()
            ->assertSee('English Private');
    }

    public function test_substitute_action_creates_change_and_reset_removes_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $t2 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $schedule = $this->makeSchedule($t1);

        $this->actingAs($admin)->post(route('schedule-changes.substitute'), [
            'teacher_schedule_id' => $schedule->id,
            'date' => $this->monday,
            'substitute_teacher_id' => $t2->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('schedule_changes', [
            'teacher_schedule_id' => $schedule->id,
            'substitute_teacher_id' => $t2->id,
        ]);

        $this->actingAs($admin)->post(route('schedule-changes.reset'), [
            'teacher_schedule_id' => $schedule->id,
            'date' => $this->monday,
        ])->assertRedirect();

        $this->assertDatabaseCount('schedule_changes', 0);
    }

    public function test_assign_substitute_from_leave_creates_changes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $t1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $t2 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $schedule = $this->makeSchedule($t1);
        $leave = TeacherLeave::query()->create([
            'teacher_id' => $t1->id,
            'date' => $this->monday,
            'status' => TeacherLeave::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)->post(route('teacher-leaves.assign-substitute', $leave), [
            'substitute_teacher_id' => $t2->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('schedule_changes', [
            'teacher_schedule_id' => $schedule->id,
            'substitute_teacher_id' => $t2->id,
            'teacher_leave_id' => $leave->id,
        ]);
    }

    public function test_calendar_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('schedule-calendar.index'))->assertForbidden();
    }
}
