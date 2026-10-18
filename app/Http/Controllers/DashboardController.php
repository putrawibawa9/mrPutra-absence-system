<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Registration;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherScheduleController;
use App\Support\WeeklyDay;
use App\Services\ScheduleOccurrenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ScheduleOccurrenceService $occurrences)
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
        // Program murid diturunkan dari divisi kelas AKTIF yang dia ikuti.
        // Kelas nonaktif tidak dihitung (murid di kelas nonaktif = tidak aktif belajar).
        $codingStudents = Student::active()
            ->whereHas('classrooms', fn ($query) => $query->where('division', Student::PROGRAM_CODING)->where('is_active', true))
            ->count();
        $englishStudents = Student::active()
            ->whereHas('classrooms', fn ($query) => $query->where('division', Student::PROGRAM_ENGLISH)->where('is_active', true))
            ->count();
        $mySchedule = collect();
        $myAvailability = collect();

        if (auth()->user()->isTeacher()) {
            $teacherId = auth()->id();
            $mySchedule = TeacherScheduleController::groupByDay(
                TeacherSchedule::query()
                    ->with('student')
                    ->where('is_active', true)
                    ->where(fn ($query) => $query->where('teacher_id', $teacherId)->orWhere('co_teacher_id', $teacherId))
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

        // Kelas pada tanggal terpilih (default hari ini) — bisa dinavigasi
        // ke kemarin/besok/lusa dll lewat ?date=YYYY-MM-DD.
        $selectedDate = Carbon::today();
        if ($request->filled('date')) {
            try {
                $selectedDate = Carbon::parse((string) $request->input('date'))->startOfDay();
            } catch (\Exception) {
                $selectedDate = Carbon::today();
            }
        }
        $dayKey = strtolower($selectedDate->englishDayOfWeek);
        // Occurrence tanggal terpilih — sudah memperhitungkan pengganti/pindah/batal.
        // Kelas nonaktif tetap tak tampil; sesi yang "dipindah keluar" disembunyikan.
        $todaysClasses = $occurrences->forDate($selectedDate)
            ->filter(fn ($o) => $o->classroom === null || $o->classroom->is_active)
            ->filter(fn ($o) => $o->status !== ScheduleOccurrenceService::STATUS_MOVED_OUT)
            ->values();
        $todayLabel = WeeklyDay::label($dayKey);

        // Murid yang les pada tanggal terpilih tapi sisa pertemuannya sudah habis
        // (<= 0), supaya admin langsung tahu siapa yang perlu ditagih/perpanjang.
        $habisToday = $todaysClasses
            ->reject(fn ($o) => $o->status === ScheduleOccurrenceService::STATUS_CANCELLED)
            ->flatMap(function ($o) {
                if (! $o->classroom) {
                    return collect();
                }

                return $o->classroom->students
                    ->filter(fn (Student $student) => $student->getNetTokenBalance() <= 0)
                    ->map(fn (Student $student) => (object) ['schedule' => $o->schedule, 'student' => $student]);
            })
            ->unique(fn ($row) => $row->student->id)
            ->values();

        // Sesi yang SUDAH diabsen pada tanggal terpilih — untuk dibandingkan
        // dengan jadwal. Grup = AttendanceBatch; private = Attendance tanpa batch.
        $attendedBatches = AttendanceBatch::query()
            ->whereDate('date', $selectedDate)
            ->with(['teacher', 'game', 'attendances.student'])
            ->orderBy('id')
            ->get()
            ->map(fn (AttendanceBatch $batch) => (object) [
                'label' => $batch->title ?: 'Kelas',
                'teacher' => $batch->teacher?->name,
                'students' => $batch->attendances->pluck('student.name')->filter()->join(', '),
                'game' => $batch->game?->label(),
                'materi' => (bool) $batch->materi_done,
                'is_group' => true,
            ]);

        $attendedPrivate = Attendance::query()
            ->whereDate('date', $selectedDate)
            ->whereNull('attendance_batch_id')
            ->with(['teacher', 'game', 'student.classrooms'])
            ->orderBy('id')
            ->get()
            ->map(fn (Attendance $attendance) => (object) [
                'label' => $attendance->student?->classrooms->first()?->name ?? $attendance->student?->name ?? 'Kelas',
                'teacher' => $attendance->teacher?->name,
                'students' => $attendance->student?->name,
                'game' => $attendance->game?->label(),
                'materi' => (bool) $attendance->materi_done,
                'is_group' => false,
            ]);

        $attendedToday = $attendedBatches->concat($attendedPrivate)->values();

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
            'selectedDate',
            'habisToday',
            'attendedToday',
        ));
    }
}
