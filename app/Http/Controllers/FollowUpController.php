<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Token;
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
     * Murid non-aktif (sudah keluar) untuk diminta pesan, kesan & saran.
     */
    public function inactive()
    {
        $students = Student::query()
            ->where('is_active', false)
            ->orderByDesc('deactivated_at')
            ->orderBy('name')
            ->get();

        return view('follow-up.inactive', ['students' => $students]);
    }
}
