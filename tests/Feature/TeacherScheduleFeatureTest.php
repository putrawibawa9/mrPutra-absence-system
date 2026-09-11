<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherScheduleFeatureTest extends TestCase
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

    public function test_admin_can_create_teacher_schedule_and_teacher_can_view_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Wulan']);
        $classroom = $this->classroom();

        $response = $this->actingAs($admin)->post(route('teacher-schedules.store'), [
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('teacher-schedules.index', absolute: false));

        // Jam selesai otomatis = 17:00 + 70 menit = 18:10; judul mengikuti nama kelas.
        $this->assertDatabaseHas('teacher_schedules', [
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => $classroom->name,
            'day_of_week' => 'monday',
            'start_time' => '17:00',
            'end_time' => '18:10',
        ]);

        $this->actingAs($teacher)
            ->get(route('my-schedule.index'))
            ->assertOk()
            ->assertSee('Jadwal Mingguan Saya')
            ->assertSee('Senin')
            ->assertSee('17:00 - 18:10')
            ->assertSee('English · Semi · Kids', false);
    }

    public function test_classes_today_lists_only_todays_classes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Pak Guru']);
        $classroom = $this->classroom();
        $student = \App\Models\Student::query()->create(['name' => 'Murid Hari Ini', 'phone' => '0811', 'is_active' => true]);
        $classroom->students()->attach($student->id);

        $today = strtolower(now()->englishDayOfWeek);
        $tomorrow = strtolower(now()->addDay()->englishDayOfWeek);

        TeacherSchedule::create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'title' => $classroom->name, 'day_of_week' => $today, 'start_time' => '10:00', 'end_time' => '11:10', 'is_active' => true]);
        TeacherSchedule::create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'title' => $classroom->name, 'day_of_week' => $tomorrow, 'start_time' => '14:00', 'end_time' => '15:10', 'is_active' => true]);

        $this->actingAs($admin)->get(route('classes.today'))
            ->assertOk()
            ->assertSee('Kelas Hari Ini')
            ->assertSee('Murid Hari Ini')
            ->assertSee('Pak Guru')
            ->assertSee('10:00 - 11:10')
            ->assertDontSee('14:00 - 15:10');

        $this->actingAs($teacher)->get(route('classes.today'))->assertForbidden();
    }

    public function test_overlapping_schedule_is_allowed_conflict_check_disabled(): void
    {
        // Validasi bentrok jadwal dinonaktifkan sementara: jadwal yang tumpang
        // tindih untuk guru yang sama kini boleh dibuat.
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $classroom = $this->classroom();

        TeacherSchedule::create([
            'teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00:00',
            'end_time' => '18:30:00',
            'is_active' => true,
        ]);

        $response = $this->from(route('teacher-schedules.create'))
            ->actingAs($admin)
            ->post(route('teacher-schedules.store'), [
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => 'monday',
                'start_time' => '18:00', // auto end 19:10 -> tumpang tindih, tapi diizinkan
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('teacher-schedules.index', absolute: false));
        $response->assertSessionHasNoErrors();
        $this->assertSame(2, TeacherSchedule::query()->where('teacher_id', $teacher->id)->count());
    }

    public function test_schedule_must_fit_active_teacher_availability_when_availability_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $classroom = $this->classroom();

        TeacherAvailability::create([
            'teacher_id' => $teacher->id,
            'day_of_week' => 'monday',
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => true,
        ]);

        $response = $this->from(route('teacher-schedules.create'))
            ->actingAs($admin)
            ->post(route('teacher-schedules.store'), [
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'day_of_week' => 'monday',
                'start_time' => '17:00', // auto end 18:10, di luar ketersediaan 17:00-18:00
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('teacher-schedules.create', absolute: false));
        $response->assertSessionHasErrors('start_time');
    }
}
