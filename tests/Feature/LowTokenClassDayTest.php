<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LowTokenClassDayTest extends TestCase
{
    use RefreshDatabase;

    private function lowStudent(string $name): Student
    {
        $student = Student::query()->create([
            'name' => $name,
            'phone' => '08123456789',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 8,
            'remaining_sessions' => 1,
            'price_amount' => 800000,
            'amount_paid' => 800000,
            'payment_date' => now()->toDateString(),
        ]);

        return $student;
    }

    private function classroom(): Classroom
    {
        return Classroom::query()->create([
            'name' => 'Kelas '.uniqid(),
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_KIDS,
        ]);
    }

    public function test_wa_button_is_gated_to_the_students_class_day(): void
    {
        // Kunci "hari ini" ke Senin supaya deterministik.
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:00:00')); // Monday
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        // Murid A: ada kelas HARI INI (Senin).
        $today = $this->lowStudent('Murid Hari Ini');
        $roomToday = $this->classroom();
        $today->classrooms()->attach($roomToday->id);
        TeacherSchedule::create(['teacher_id' => $teacher->id, 'classroom_id' => $roomToday->id, 'title' => $roomToday->name, 'day_of_week' => 'monday', 'start_time' => '10:00', 'end_time' => '11:10', 'is_active' => true]);

        // Murid B: ada jadwal tapi hari LAIN (Rabu).
        $other = $this->lowStudent('Murid Hari Lain');
        $roomOther = $this->classroom();
        $other->classrooms()->attach($roomOther->id);
        TeacherSchedule::create(['teacher_id' => $teacher->id, 'classroom_id' => $roomOther->id, 'title' => $roomOther->name, 'day_of_week' => 'wednesday', 'start_time' => '10:00', 'end_time' => '11:10', 'is_active' => true]);

        // Murid C: belum ada jadwal sama sekali.
        $none = $this->lowStudent('Murid Tanpa Jadwal');

        $response = $this->actingAs($admin)->get(route('follow-up.low-token'));

        $response->assertOk();
        $response->assertViewHas('lowTokenStudents', function ($students) use ($today, $other, $none) {
            $a = $students->firstWhere('id', $today->id);
            $b = $students->firstWhere('id', $other->id);
            $c = $students->firstWhere('id', $none->id);

            return $a->has_class_today === true
                && $b->has_class_today === false && $b->has_schedule === true && $b->class_days_label === 'Rabu'
                && $c->has_class_today === false && $c->has_schedule === false;
        });

        Carbon::setTestNow();
    }
}
