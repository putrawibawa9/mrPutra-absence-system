<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Token;
use App\Services\AttendanceTeacherFeeService;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();

        // Total uang masuk (kas) pada PERIODE filter — uang yang benar-benar
        // diterima (jumlah cicilan/pembayaran murid) dalam rentang tanggal.
        $cashInPeriod = (int) PaymentInstallment::query()
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->sum('amount');

        // === Pendapatan diakui secara akrual (saat jasa diberikan) ===

        // 1) Pendapatan kelas: tiap sesi yang dihadiri "memakan" satu token,
        //    dan diakui sebesar harga-per-sesi dari pembayaran yang tertaut.
        $tokenAttendances = Attendance::query()
            ->whereNotNull('payment_id')
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->with(['payment', 'student', 'batch'])
            ->get()
            ->filter(fn (Attendance $attendance) => $attendance->payment
                && in_array($attendance->payment->source_type, Payment::TOKEN_SOURCES, true));

        // Token "forfeited": murid yang bolos di kelas synchronous (live) — tokennya
        // tetap hangus & tetap dihitung sebagai pendapatan (per tanggal pertemuan/batch).
        $forfeitedTokens = Token::query()
            ->where('status', Token::STATUS_FORFEITED)
            ->whereNotNull('attendance_batch_id')
            ->whereHas('batch', fn ($query) => $query->whereDate('date', '>=', $dateFrom)->whereDate('date', '<=', $dateTo))
            ->with(['payment', 'batch', 'student'])
            ->get()
            ->filter(fn (Token $token) => $token->payment !== null);

        $forfeitByBatch = $forfeitedTokens->groupBy('attendance_batch_id');
        $forfeitRevenue = (int) $forfeitedTokens->sum(fn (Token $token) => $token->payment->pricePerSession());
        $forfeitCount = $forfeitedTokens->count();

        // Pendapatan kelas (token) = sesi yang dihadiri + token hangus (synchronous).
        $tokenRevenue = (int) $tokenAttendances->sum(fn (Attendance $attendance) => $attendance->payment->pricePerSession())
            + $forfeitRevenue;

        // 2) Pendapatan buku/modul: diakui saat penjualan (tanggal pembayaran).
        $bookRevenue = (int) Payment::query()
            ->where('source_type', Payment::SOURCE_BOOK)
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->sum('price_amount');

        $revenue = $tokenRevenue + $bookRevenue;

        // === Expense yang terjadi pada periode (termasuk fee guru per pertemuan) ===
        $expenses = Expense::query()
            ->with(['category', 'creator'])
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->latest('expense_date')
            ->latest('id')
            ->get();

        $expenseTotal = (int) $expenses->sum('amount');
        $feeGuruExpenses = $expenses->filter(
            fn (Expense $expense) => $expense->category?->name === AttendanceTeacherFeeService::CATEGORY_NAME
        );
        $teacherFeeTotal = (int) $feeGuruExpenses->sum('amount');

        // === Breakdown per tipe sesi: private (1-on-1) vs grup (>1 peserta) ===
        // Private = attendance tanpa batch; Grup = attendance di bawah satu batch.
        // Margin kotor = pendapatan tipe tsb - fee guru tipe tsb (sebelum opex bersama).
        $privateAttendances = $tokenAttendances->whereNull('attendance_batch_id');
        $groupAttendances = $tokenAttendances->whereNotNull('attendance_batch_id');

        // Token hangus (synchronous) selalu masuk kategori grup/semi.
        $privateRevenue = (int) $privateAttendances->sum(fn (Attendance $attendance) => $attendance->payment->pricePerSession());
        $groupRevenue = (int) $groupAttendances->sum(fn (Attendance $attendance) => $attendance->payment->pricePerSession())
            + $forfeitRevenue;

        // Fee guru private tertaut attendance_id; fee guru grup tertaut attendance_batch_id.
        $privateFee = (int) $feeGuruExpenses->whereNotNull('attendance_id')->sum('amount');
        $groupFee = (int) $feeGuruExpenses->whereNotNull('attendance_batch_id')->sum('amount');

        $privateSessions = $privateAttendances->count();
        $groupParticipants = $groupAttendances->count() + $forfeitCount;
        $groupSessions = $groupAttendances->pluck('attendance_batch_id')
            ->merge($forfeitByBatch->keys())
            ->unique()->count();

        // Fee guru per sesi untuk perhitungan net income per pertemuan.
        $feeByAttendance = $feeGuruExpenses->whereNotNull('attendance_id')
            ->groupBy('attendance_id')->map->sum('amount');
        $feeByBatch = $feeGuruExpenses->whereNotNull('attendance_batch_id')
            ->groupBy('attendance_batch_id')->map->sum('amount');

        // Net income per sesi = pendapatan kelas - fee guru (private per attendance, grup per batch).
        $privateNetEntries = $privateAttendances
            ->map(function (Attendance $attendance) use ($feeByAttendance) {
                $gross = (int) $attendance->payment->pricePerSession();
                $fee = (int) ($feeByAttendance[$attendance->id] ?? 0);

                return (object) [
                    'id' => 'net-'.$attendance->id,
                    'date' => $attendance->date,
                    'name' => $attendance->student?->name ?? 'Unknown Student',
                    'label' => 'Sesi private',
                    'gross' => $gross,
                    'fee' => $fee,
                    'net' => $gross - $fee,
                ];
            });

        $groupedAttendances = $groupAttendances->groupBy('attendance_batch_id');
        $groupBatchIds = $groupedAttendances->keys()
            ->merge($forfeitByBatch->keys())
            ->unique()
            ->values();

        $groupNetEntries = $groupBatchIds
            ->map(function ($batchId) use ($groupedAttendances, $forfeitByBatch, $feeByBatch) {
                $attendances = $groupedAttendances->get($batchId, collect());
                $forfeits = $forfeitByBatch->get($batchId, collect());

                $gross = (int) $attendances->sum(fn (Attendance $attendance) => $attendance->payment->pricePerSession())
                    + (int) $forfeits->sum(fn (Token $token) => $token->payment->pricePerSession());
                $count = $attendances->count() + $forfeits->count();
                $fee = (int) ($feeByBatch[$batchId] ?? 0);

                $batch = $attendances->first()?->batch ?? $forfeits->first()?->batch;
                $date = $attendances->first()?->date ?? $batch?->date;

                // Satu nama murid sebagai penanda kelas (admin mungkin tak hafal nama kelas).
                $names = $attendances->map(fn (Attendance $attendance) => $attendance->student?->name)
                    ->merge($forfeits->map(fn (Token $token) => $token->student?->name))
                    ->filter()
                    ->values();
                $studentHint = $names->first();
                if ($names->count() > 1) {
                    $studentHint .= ' +'.($names->count() - 1).' lainnya';
                }

                return (object) [
                    'id' => 'net-batch-'.$batchId,
                    'date' => $date,
                    'name' => $batch?->title ?: 'Kelas Grup',
                    'student_hint' => $studentHint,
                    'label' => $count.' peserta',
                    'gross' => $gross,
                    'fee' => $fee,
                    'net' => $gross - $fee,
                ];
            })
            ->values();

        $sessionNetEntries = $privateNetEntries
            ->concat($groupNetEntries)
            ->sortByDesc(fn (object $entry) => $entry->date->format('Y-m-d').'|'.$entry->id)
            ->values();

        // Expense entries yang ditampilkan: sembunyikan fee guru (sudah diringkas di kartu & list net per sesi).
        $operationalExpenses = $expenses
            ->reject(fn (Expense $expense) => $expense->category?->name === AttendanceTeacherFeeService::CATEGORY_NAME)
            ->values();

        $sessionTypeBreakdown = [
            'private' => [
                'revenue' => $privateRevenue,
                'fee' => $privateFee,
                'margin' => $privateRevenue - $privateFee,
                'sessions' => $privateSessions,
                'avg_margin' => $privateSessions > 0 ? (int) round(($privateRevenue - $privateFee) / $privateSessions) : 0,
            ],
            'group' => [
                'revenue' => $groupRevenue,
                'fee' => $groupFee,
                'margin' => $groupRevenue - $groupFee,
                'sessions' => $groupSessions,
                'participants' => $groupParticipants,
                'avg_margin' => $groupSessions > 0 ? (int) round(($groupRevenue - $groupFee) / $groupSessions) : 0,
                'avg_participants' => $groupSessions > 0 ? round($groupParticipants / $groupSessions, 1) : 0,
            ],
        ];

        $netProfit = $revenue - $expenseTotal;
        $margin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0;

        // Jumlah pertemuan (sesi yang digelar) pada periode — batch dihitung sekali.
        $meetingCount = Attendance::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->get(['id', 'attendance_batch_id'])
            ->groupBy(fn (Attendance $attendance) => $attendance->attendance_batch_id
                ? 'batch-'.$attendance->attendance_batch_id
                : 'single-'.$attendance->id)
            ->count();

        $tokenSessionCount = $tokenAttendances->count() + $forfeitCount;
        $averageRevenuePerMeeting = $tokenSessionCount > 0
            ? (int) round($tokenRevenue / $tokenSessionCount)
            : 0;

        $incomeBySource = [
            'student_payments' => $tokenRevenue,
            'module_payments' => $bookRevenue,
        ];

        return view('cash-flow.index', [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'revenue' => $revenue,
            'cashInPeriod' => $cashInPeriod,
            'expenseTotal' => $expenseTotal,
            'teacherFeeTotal' => $teacherFeeTotal,
            'netProfit' => $netProfit,
            'margin' => $margin,
            'averageRevenuePerMeeting' => $averageRevenuePerMeeting,
            'meetingCount' => $meetingCount,
            'tokenSessionCount' => $tokenSessionCount,
            'sessionTypeBreakdown' => $sessionTypeBreakdown,
            'sessionNetEntries' => $sessionNetEntries,
            'expenses' => $operationalExpenses,
            'incomeBySource' => $incomeBySource,
        ]);
    }
}
