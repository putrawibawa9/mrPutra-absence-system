<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomJournalTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $name): Student
    {
        return Student::query()->create([
            'name' => $name,
            'phone' => '0811'.random_int(1000, 9999),
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    public function test_private_class_journal_lists_student_attendance_journals_newest_first(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Bu Guru']);

        $classroom = Classroom::query()->create([
            'name' => 'English · Private · Kids',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
        ]);
        $student = $this->student('Devana');
        $classroom->students()->attach($student->id);

        Attendance::query()->create(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'date' => '2026-09-01', 'learning_journal' => 'Sesi lama: alphabet']);
        Attendance::query()->create(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'date' => '2026-09-10', 'learning_journal' => 'Sesi baru: present tense']);

        $response = $this->actingAs($admin)->get(route('classrooms.journal', $classroom));

        $response->assertOk();
        $response->assertSee('Sesi baru: present tense');
        $response->assertSee('Sesi lama: alphabet');
        $response->assertViewHas('entries', function ($entries) {
            return $entries->count() === 2 && $entries->first()->journal === 'Sesi baru: present tense';
        });
    }

    public function test_group_class_journal_lists_batch_journals(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $classroom = Classroom::query()->create([
            'name' => 'English · Semi · Teens/Adult',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_TEENS_ADULT,
        ]);
        $a = $this->student('Komang');
        $b = $this->student('Putu');
        $classroom->students()->attach([$a->id, $b->id]);

        $batch = AttendanceBatch::query()->create(['title' => $classroom->name, 'teacher_id' => $teacher->id, 'date' => '2026-09-12', 'learning_journal' => 'Grup: debate practice']);
        Attendance::query()->create(['attendance_batch_id' => $batch->id, 'student_id' => $a->id, 'teacher_id' => $teacher->id, 'date' => '2026-09-12', 'learning_journal' => 'Grup: debate practice']);
        Attendance::query()->create(['attendance_batch_id' => $batch->id, 'student_id' => $b->id, 'teacher_id' => $teacher->id, 'date' => '2026-09-12', 'learning_journal' => 'Grup: debate practice']);

        $response = $this->actingAs($admin)->get(route('classrooms.journal', $classroom));

        $response->assertOk();
        $response->assertSee('Grup: debate practice');
        $response->assertSee('Komang');
        $response->assertSee('Putu');
        $response->assertViewHas('entries', fn ($entries) => $entries->count() === 1);
    }

    public function test_index_search_matches_class_name_and_student_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $roomA = Classroom::query()->create(['name' => 'Kelas Alpha', 'division' => Classroom::DIVISION_ENGLISH, 'format' => Classroom::FORMAT_PRIVATE, 'age_group' => Classroom::AGE_KIDS]);
        $roomB = Classroom::query()->create(['name' => 'Kelas Beta', 'division' => Classroom::DIVISION_CODING, 'format' => Classroom::FORMAT_PRIVATE, 'age_group' => Classroom::AGE_KIDS]);
        $siti = $this->student('Siti Rahma');
        $roomB->students()->attach($siti->id);

        // Cari nama kelas.
        $this->actingAs($admin)->get(route('classrooms.index', ['search' => 'Alpha']))
            ->assertOk()->assertSee('Kelas Alpha')->assertDontSee('Kelas Beta');

        // Cari nama murid -> muncul kelas si murid.
        $this->actingAs($admin)->get(route('classrooms.index', ['search' => 'Siti']))
            ->assertOk()->assertSee('Kelas Beta')->assertDontSee('Kelas Alpha');
    }
}
