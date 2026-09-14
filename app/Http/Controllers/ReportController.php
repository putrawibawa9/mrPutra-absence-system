<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;

class ReportController extends Controller
{
    /**
     * LTV per murid: berapa kali dia bayar (cycle token), total token dibeli,
     * dan total nilai yang pernah dibayar — untuk analisa retensi & LTV.
     */
    public function ltv()
    {
        $students = Student::query()
            ->withCount([
                'payments as token_payment_count' => fn ($query) => $query->whereIn('source_type', Payment::TOKEN_SOURCES),
                'payments as all_payment_count',
            ])
            ->withSum(['payments as ltv_amount' => fn ($query) => $query], 'amount_paid')
            ->withSum(['payments as tokens_purchased' => fn ($query) => $query->whereIn('source_type', Payment::TOKEN_SOURCES)], 'total_sessions')
            ->withMin('payments as first_payment_date', 'payment_date')
            ->withMax('payments as last_payment_date', 'payment_date')
            ->get()
            ->filter(fn (Student $student) => (int) $student->all_payment_count > 0)
            ->sortByDesc(fn (Student $student) => (int) ($student->ltv_amount ?? 0))
            ->values();

        $payersCount = $students->count();
        $repeatCount = $students->filter(fn (Student $s) => (int) $s->token_payment_count >= 2)->count();
        $totalLtv = (int) $students->sum(fn (Student $s) => (int) ($s->ltv_amount ?? 0));
        $avgCycles = $payersCount > 0
            ? round($students->avg(fn (Student $s) => (int) $s->token_payment_count), 1)
            : 0;

        return view('reports.ltv', compact('students', 'payersCount', 'repeatCount', 'totalLtv', 'avgCycles'));
    }
}
