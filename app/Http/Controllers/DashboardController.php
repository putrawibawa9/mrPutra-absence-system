<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherScheduleController;
use App\Support\WeeklyDay;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Dashboard hanya untuk admin. Guru diarahkan ke halaman absensi.
        if (auth()->user()->isTeacher()) {
            return redirect()->route('attendances.index');
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $newRegistrationsThisMonth = Student::query()
            ->whereBetween('registration_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->count();

        $studentsExitedThisMonth = Student::query()
            ->whereNotNull('deactivated_at')
            ->whereBetween('deactivated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $activeStudents = Student::active()->count();
        $codingStudents = Student::query()
            ->where('program_type', Student::PROGRAM_CODING)
            ->count();
        $englishStudents = Student::query()
            ->where('program_type', Student::PROGRAM_ENGLISH)
            ->count();
        $mySchedule = collect();
        $myAvailability = collect();

        if (auth()->user()->isTeacher()) {
            $mySchedule = TeacherScheduleController::groupByDay(
                auth()->user()->teacherSchedules()
                    ->with('student')
                    ->where('is_active', true)
                    ->orderByRaw("CASE day_of_week
                        WHEN 'monday' THEN 1
                        WHEN 'tuesday' THEN 2
                        WHEN 'wednesday' THEN 3
                        WHEN 'thursday' THEN 4
                        WHEN 'friday' THEN 5
                        WHEN 'saturday' THEN 6
                        WHEN 'sunday' THEN 7
                        ELSE 8
                    END")
                    ->orderBy('start_time')
                    ->get()
            );

            $myAvailability = TeacherAvailabilityController::groupByDay(
                auth()->user()->teacherAvailabilities()
                    ->where('is_active', true)
                    ->orderByRaw("CASE day_of_week
                        WHEN 'monday' THEN 1
                        WHEN 'tuesday' THEN 2
                        WHEN 'wednesday' THEN 3
                        WHEN 'thursday' THEN 4
                        WHEN 'friday' THEN 5
                        WHEN 'saturday' THEN 6
                        WHEN 'sunday' THEN 7
                        ELSE 8
                    END")
                    ->orderBy('start_time')
                    ->get()
            );
        }

        $pendingRegistrations = Registration::query()
            ->where('status', Registration::STATUS_PENDING)
            ->count();

        // Kelas yang les hari ini (dari jadwal mingguan) — tampil langsung di dashboard.
        $todayKey = strtolower(now()->englishDayOfWeek);
        $todaysClasses = TeacherSchedule::query()
            ->with(['teacher', 'classroom.students'])
            ->where('is_active', true)
            ->where('day_of_week', $todayKey)
            ->orderBy('start_time')
            ->get();
        $todayLabel = WeeklyDay::label($todayKey);

        return view('dashboard', compact(
            'newRegistrationsThisMonth',
            'studentsExitedThisMonth',
            'activeStudents',
            'codingStudents',
            'englishStudents',
            'pendingRegistrations',
            'mySchedule',
            'myAvailability',
            'todaysClasses',
            'todayLabel',
        ));
    }
}
