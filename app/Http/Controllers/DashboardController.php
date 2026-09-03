<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherScheduleController;
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
        // Alert token menipis: murid aktif dgn sisa token <= ambang (default 1),
        // diurutkan paling mendesak (paling sedikit/0) dulu, siap diingatkan via WA.
        $lowTokenStudents = Student::active()
            ->withSum('payments', 'remaining_sessions')
            ->withCount(['attendances as token_debt_count' => fn ($query) => $query->whereNull('payment_id')])
            ->with('latestSessionPayment')
            ->get()
            ->filter(fn (Student $student) => (int) ($student->payments_sum_remaining_sessions ?? 0) <= Student::LOW_SESSION_THRESHOLD)
            ->sortBy(fn (Student $student) => (int) ($student->payments_sum_remaining_sessions ?? 0))
            ->values();
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

        return view('dashboard', compact(
            'newRegistrationsThisMonth',
            'studentsExitedThisMonth',
            'activeStudents',
            'codingStudents',
            'englishStudents',
            'lowTokenStudents',
            'mySchedule',
            'myAvailability',
        ));
    }
}
