<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\Token;
use App\Support\WeeklyDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FollowUpController extends Controller
{
    /**
     * Murid aktif yang tidak hadir beberapa kali BERTURUT-TURUT.
     *
     * "Tidak hadir" = token forfeited (murid tercatat absen di sesi grup).
     * Absen berturut-turut = jumlah absen yang terjadi SETELAH pertemuan terakhir
     * yang murid benar-benar hadir (baris attendance). Jadi murid yang sudah kembali
     * hadir otomatis reset dan tidak tertandai.
     */
    public function absent()
    {
        $students = Student::active()->orderBy('name')->get(['id', 'name', 'phone']);
        $studentIds = $students->pluck('id');

        // Tanggal kehadiran terakhir per murid (string 'Y-m-d').
        $lastPresent = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, MAX(date) as last_date')
            ->groupBy('student_id')
            ->pluck('last_date', 'student_id');

        // Semua token absen (forfeited) beserta tanggal sesinya (dari batch, fallback forfeited_at).
        $forfeitsByStudent = Token::query()
            ->where('status', Token::STATUS_FORFEITED)
            ->whereIn('student_id', $studentIds)
            ->with('batch:id,date')
            ->get(['id', 'student_id', 'attendance_batch_id', 'forfeited_at'])
            ->groupBy('student_id');

        $rows = $students->map(function (Student $student) use ($lastPresent, $forfeitsByStudent): object {
            $absenceDates = ($forfeitsByStudent->get($student->id) ?? collect())
                ->map(fn (Token $token) => $token->batch?->date ?? $token->forfeited_at)
                ->filter()
                ->map(fn ($date) => Carbon::parse($date))
                ->sortByDesc(fn (Carbon $date) => $date->getTimestamp())
                ->values();

            $lastPresentDate = $lastPresent->get($student->id);

            // Absen berturut-turut = absen yang tanggalnya melewati kehadiran terakhir.
            $streak = $lastPresentDate
                ? $absenceDates->filter(fn (Carbon $date) => $date->toDateString() > $lastPresentDate)->count()
                : $absenceDates->count();

            return (object) [
                'student' => $student,
                'streak' => $streak,
                'last_absence' => $absenceDates->first(),
                'whatsapp_url' => $student->attendanceCheckInWhatsAppUrl(),
            ];
        })
            ->filter(fn (object $row) => $row->streak >= Student::CONSECUTIVE_ABSENCE_ALERT)
            ->sortByDesc('streak')
            ->values();

        return view('follow-up.absent', ['rows' => $rows]);
    }

    /**
     * Murid aktif dengan sisa token menipis (<= ambang), untuk diingatkan via WA.
     */
    public function lowToken()
    {
        $lowTokenStudents = Student::active()
            ->withSum('payments', 'remaining_sessions')
            ->withCount(['attendances as token_debt_count' => fn ($query) => $query->whereNull('payment_id')])
            ->with(['latestSessionPayment', 'classrooms:id'])
            ->get()
            ->filter(fn (Student $student) => (int) ($student->payments_sum_remaining_sessions ?? 0) <= Student::LOW_SESSION_THRESHOLD)
            ->sortBy(fn (Student $student) => (int) ($student->payments_sum_remaining_sessions ?? 0))
            ->values();

        // Hari les tiap murid diambil dari jadwal mingguan aktif kelasnya. Tombol WA
        // hanya aktif di hari murid ada kelas, supaya admin tinggal klik di hari itu.
        $todayKey = strtolower(now()->englishDayOfWeek);
        $daysByClassroom = TeacherSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('classroom_id')
            ->get(['classroom_id', 'day_of_week'])
            ->groupBy('classroom_id')
            ->map(fn ($group) => $group->pluck('day_of_week')->unique());

        $lowTokenStudents->each(function (Student $student) use ($daysByClassroom, $todayKey): void {
            $dayKeys = $student->classrooms
                ->flatMap(fn ($classroom) => $daysByClassroom->get($classroom->id, collect()))
                ->unique();
            $orderedDays = collect(WeeklyDay::values())->filter(fn ($key) => $dayKeys->contains($key))->values();

            $student->setAttribute('has_schedule', $orderedDays->isNotEmpty());
            $student->setAttribute('has_class_today', $orderedDays->contains($todayKey));
            $student->setAttribute('class_days_label', $orderedDays->map(fn ($key) => WeeklyDay::label($key))->join(', '));
        });

        return view('follow-up.low-token', [
            'lowTokenStudents' => $lowTokenStudents,
            'todayLabel' => WeeklyDay::label($todayKey),
        ]);
    }

    /**
     * Murid non-aktif (sudah keluar) untuk diminta pesan, kesan & saran.
     */
    public function inactive()
    {
        $students = Student::query()
            ->where('is_active', false)
            ->withCount('feedbacks')
            ->orderByDesc('deactivated_at')
            ->orderBy('name')
            ->get();

        return view('follow-up.inactive', ['students' => $students]);
    }

    /**
     * Kumpulan feedback yang sudah dikirim murid lewat form publik.
     */
    public function feedback()
    {
        $feedbacks = Feedback::query()->with('student')->latest()->get();
        $average = $feedbacks->isNotEmpty() ? round($feedbacks->avg('rating'), 1) : null;

        return view('follow-up.feedback', compact('feedbacks', 'average'));
    }
}
