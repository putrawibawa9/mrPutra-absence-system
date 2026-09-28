<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCoTeacherTest extends TestCase
{
    use RefreshDatabase;

    private function classroom(): Classroom
    {
        return Classroom::create([
            'name' => 'English · Semi · Kids',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_assign_a_co_teacher_to_a_schedule(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Utama']);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Trainee']);
        $classroom = $this->classroom();

        $this->actingAs($admin)->post(route('teacher-schedules.store'), [
            'teacher_id' => $teacher->id,
            'co_teacher_id' => $coTeacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'is_active' => '1',
        ])->assertRedirect(route('teacher-schedules.index', absolute: false));

        $this->assertDatabaseHas('teacher_schedules', [
            'teacher_id' => $teacher->id,
            'co_teacher_id' => $coTeacher->id,
            'classroom_id' => $classroom->id,
        ]);
    }

    public function test_co_teacher_cannot_be_same_as_teacher(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $classroom = $this->classroom();

        $this->actingAs($admin)->post(route('teacher-schedules.store'), [
            'teacher_id' => $teacher->id,
            'co_teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'is_active' => '1',
        ])->assertSessionHasErrors('co_teacher_id');

        $this->assertSame(0, TeacherSchedule::query()->count());
    }

    public function test_co_teacher_sees_schedule_in_my_schedule_with_role(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Utama']);
        $coTeacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Co']);
        $classroom = $this->classroom();

        TeacherSchedule::create([
            'teacher_id' => $teacher->id,
            'co_teacher_id' => $coTeacher->id,
            'classroom_id' => $classroom->id,
            'title' => $classroom->name,
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'end_time' => '18:10',
            'is_active' => true,
        ]);

        // Co-teacher melihatnya dengan peran Co-teacher.
        $this->actingAs($coTeacher)->get(route('my-schedule.index'))
            ->assertOk()
            ->assertSee('17:00 - 18:10')
            ->assertSee('Co-teacher')
            ->assertSee('Guru utama: Guru Utama');

        // Guru utama melihatnya dengan peran Guru utama.
        $this->actingAs($teacher)->get(route('my-schedule.index'))
            ->assertOk()
            ->assertSee('17:00 - 18:10')
            ->assertSee('Guru utama');
    }
}
