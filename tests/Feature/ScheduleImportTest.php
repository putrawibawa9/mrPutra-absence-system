<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScheduleImportTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('jadwal.csv', $content);
    }

    public function test_admin_can_import_schedule_and_auto_create_classes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $csv = "nama_kelas,hari,jam_mulai,jam_selesai\n"
            ."Kelas A,Senin,17:00,18:30\n"
            ."Kelas A,Rabu,17:00,18:30\n"
            ."Kelas B,Selasa,14:00,15:30\n";

        $response = $this->actingAs($admin)->post(route('teacher-schedules.import.store'), [
            'file' => $this->csv($csv),
        ]);

        $response->assertRedirect(route('teacher-schedules.index'));

        $this->assertDatabaseCount('classrooms', 2);
        $this->assertDatabaseCount('teacher_schedules', 3);

        $kelasA = Classroom::query()->where('name', 'Kelas A')->firstOrFail();
        $this->assertSame(Classroom::DIVISION_ENGLISH, $kelasA->division);

        $schedule = TeacherSchedule::query()->where('classroom_id', $kelasA->id)->where('day_of_week', 'monday')->firstOrFail();
        $this->assertNull($schedule->teacher_id);
        $this->assertSame('17:00', substr((string) $schedule->start_time, 0, 5));
    }

    public function test_reimport_skips_existing_rows(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $csv = "nama_kelas,hari,jam_mulai,jam_selesai\nKelas A,Senin,17:00,18:30\n";

        $this->actingAs($admin)->post(route('teacher-schedules.import.store'), ['file' => $this->csv($csv)]);
        $this->actingAs($admin)->post(route('teacher-schedules.import.store'), ['file' => $this->csv($csv)]);

        $this->assertDatabaseCount('classrooms', 1);
        $this->assertDatabaseCount('teacher_schedules', 1);
    }

    public function test_invalid_rows_abort_whole_import(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $csv = "nama_kelas,hari,jam_mulai,jam_selesai\n"
            ."Kelas A,Senin,17:00,18:30\n"
            ."Kelas B,Funday,14:00,15:30\n"; // hari tidak valid

        $response = $this->actingAs($admin)->post(route('teacher-schedules.import.store'), ['file' => $this->csv($csv)]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('teacher_schedules', 0);
    }

    public function test_missing_header_column_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $csv = "nama_kelas,hari,jam_mulai\nKelas A,Senin,17:00\n";

        $this->actingAs($admin)->post(route('teacher-schedules.import.store'), ['file' => $this->csv($csv)])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('teacher_schedules', 0);
    }

    public function test_import_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('teacher-schedules.import'))->assertForbidden();
    }
}
