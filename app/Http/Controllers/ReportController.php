<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Token;
use App\Services\AttendanceTeacherFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Monitoring OpEx: pisahkan biaya tetap vs variabel, lalu bebankan overhead
     * tetap ke tiap kelas (rata per pertemuan) supaya biaya "sesungguhnya" per
     * kelas kelihatan — agar tidak ada kebocoran biaya yang tersamar (bocor halus).
     */
    public function opex(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();

        // === 1) Semua expense pada periode, dikelompokkan per kategori ===
        $expenses = Expense::query()
            ->with('category')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->get();

        $opexTotal = (int) $expenses->sum('amount');

        $byCategory = $expenses
            ->groupBy(fn (Expense $e) => $e->category?->id ?? 'uncategorized')
            ->map(function ($group) {
                $category = $group->first()->category;

                return (object) [
                    'name' => $category?->name ?? 'Tanpa kategori',
                    'behavior' => $category?->cost_behavior,
                    'behavior_label' => $category?->costBehaviorLabel() ?? 'Belum ditandai',
                    'amount' => (int) $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $fixedTotal = (int) $byCategory->where('behavior', ExpenseCategory::COST_FIXED)->sum('amount');
        $variableTotal = (int) $byCategory->where('behavior', ExpenseCategory::COST_VARIABLE)->sum('amount');
        $unclassifiedTotal = $opexTotal - $fixedTotal - $variableTotal;

        // Fee guru = biaya variabel yang SUDAH langsung nempel ke tiap kelas.
        $teacherFeeTotal = (int) $expenses
            ->filter(fn (Expense $e) => $e->category?->name === AttendanceTeacherFeeService::CATEGORY_NAME)
            ->sum('amount');

        // === 2) Jumlah pertemuan pada periode (batch dihitung sekali) ===
        $meetingCount = Attendance::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->get(['id', 'attendance_batch_id'])
            ->groupBy(fn (Attendance $a) => $a->attendance_batch_id
                ? 'batch-'.$a->attendance_batch_id
                : 'single-'.$a->id)
            ->count();

        // Overhead tetap dibagi rata ke tiap pertemuan.
        $overheadPerMeeting = $meetingCount > 0 ? (int) round($fixedTotal / $meetingCount) : 0;
        $feePerMeeting = $meetingCount > 0 ? (int) round($teacherFeeTotal / $meetingCount) : 0;
        $loadedCostPerMeeting = $overheadPerMeeting + $feePerMeeting;

        // === 3) Pendapatan kelas per pertemuan (akrual, seperti Cash Flow) ===
        $tokenAttendances = Attendance::query()
            ->whereNotNull('payment_id')
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->with(['payment', 'student', 'batch'])
            ->get()
            ->filter(fn (Attendance $a) => $a->payment
                && in_array($a->payment->source_type, Payment::TOKEN_SOURCES, true));

        $forfeitedTokens = Token::query()
            ->where('status', Token::STATUS_FORFEITED)
            ->whereNotNull('attendance_batch_id')
            ->whereHas('batch', fn ($q) => $q->whereDate('date', '>=', $dateFrom)->whereDate('date', '<=', $dateTo))
            ->with(['payment', 'batch', 'student'])
            ->get()
            ->filter(fn (Token $t) => $t->payment !== null);

        $forfeitByBatch = $forfeitedTokens->groupBy('attendance_batch_id');

        // Fee guru per pertemuan (untuk perhitungan net riil per kelas).
        $feeGuruExpenses = $expenses->filter(
            fn (Expense $e) => $e->category?->name === AttendanceTeacherFeeService::CATEGORY_NAME
        );
        $feeByAttendance = $feeGuruExpenses->whereNotNull('attendance_id')
            ->groupBy('attendance_id')->map->sum('amount');
        $feeByBatch = $feeGuruExpenses->whereNotNull('attendance_batch_id')
            ->groupBy('attendance_batch_id')->map->sum('amount');

        $privateAttendances = $tokenAttendances->whereNull('attendance_batch_id');
        $groupAttendances = $tokenAttendances->whereNotNull('attendance_batch_id');

        // Entri per kelas private.
        $privateEntries = $privateAttendances->map(function (Attendance $a) use ($feeByAttendance, $overheadPerMeeting) {
            $gross = (int) $a->payment->pricePerSession();
            $fee = (int) ($feeByAttendance[$a->id] ?? 0);

            return (object) [
                'date' => $a->date,
                'name' => $a->student?->name ?? 'Murid',
                'label' => 'Private',
                'gross' => $gross,
                'fee' => $fee,
                'overhead' => $overheadPerMeeting,
                'net' => $gross - $fee - $overheadPerMeeting,
            ];
        });

        // Entri per kelas grup (per batch).
        $groupedAttendances = $groupAttendances->groupBy('attendance_batch_id');
        $groupBatchIds = $groupedAttendances->keys()->merge($forfeitByBatch->keys())->unique()->values();

        $groupEntries = $groupBatchIds->map(function ($batchId) use ($groupedAttendances, $forfeitByBatch, $feeByBatch, $overheadPerMeeting) {
            $attendances = $groupedAttendances->get($batchId, collect());
            $forfeits = $forfeitByBatch->get($batchId, collect());

            $gross = (int) $attendances->sum(fn (Attendance $a) => $a->payment->pricePerSession())
                + (int) $forfeits->sum(fn (Token $t) => $t->payment->pricePerSession());
            $fee = (int) ($feeByBatch[$batchId] ?? 0);
            $count = $attendances->count() + $forfeits->count();

            $batch = $attendances->first()?->batch ?? $forfeits->first()?->batch;
            $names = $attendances->map(fn (Attendance $a) => $a->student?->name)
                ->merge($forfeits->map(fn (Token $t) => $t->student?->name))
                ->filter()->values();
            $hint = $names->first();
            if ($names->count() > 1) {
                $hint .= ' +'.($names->count() - 1);
            }

            return (object) [
                'date' => $attendances->first()?->date ?? $batch?->date,
                'name' => $hint ?: ($batch?->title ?: 'Kelas Grup'),
                'label' => 'Grup · '.$count.' peserta',
                'gross' => $gross,
                'fee' => $fee,
                'overhead' => $overheadPerMeeting,
                'net' => $gross - $fee - $overheadPerMeeting,
            ];
        });

        // Worst-first: kelas paling "bocor" muncul di atas.
        $classEntries = $privateEntries->concat($groupEntries)
            ->sortBy('net')
            ->values();

        $leakCount = $classEntries->filter(fn ($e) => $e->net < 0)->count();

        $classRevenue = (int) $classEntries->sum('gross');
        $tokenSessionCount = $classEntries->count();
        $avgRevenuePerMeeting = $tokenSessionCount > 0 ? (int) round($classRevenue / $tokenSessionCount) : 0;

        // Break-even: berapa pertemuan/bulan untuk menutup biaya tetap, dengan
        // kontribusi per pertemuan = pendapatan rata - fee guru rata.
        $contributionPerMeeting = $avgRevenuePerMeeting - $feePerMeeting;
        $breakEvenMeetings = $contributionPerMeeting > 0
            ? (int) ceil($fixedTotal / $contributionPerMeeting)
            : null;

        return view('reports.opex', [
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'opexTotal' => $opexTotal,
            'fixedTotal' => $fixedTotal,
            'variableTotal' => $variableTotal,
            'unclassifiedTotal' => $unclassifiedTotal,
            'teacherFeeTotal' => $teacherFeeTotal,
            'byCategory' => $byCategory,
            'meetingCount' => $meetingCount,
            'overheadPerMeeting' => $overheadPerMeeting,
            'feePerMeeting' => $feePerMeeting,
            'loadedCostPerMeeting' => $loadedCostPerMeeting,
            'avgRevenuePerMeeting' => $avgRevenuePerMeeting,
            'contributionPerMeeting' => $contributionPerMeeting,
            'breakEvenMeetings' => $breakEvenMeetings,
            'classEntries' => $classEntries,
            'leakCount' => $leakCount,
        ]);
    }

    /**
     * Review Bulanan: menyorot potensi salah kategori —
     *  (a) expense tanpa item, (b) item yang tercatat di >1 kategori,
     *  (c) kategori fixed yang naik >30% dari rata-rata 3 bulan sebelumnya.
     */
    public function review(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();

        // (a) Expense tanpa item pada periode.
        $withoutItem = Expense::query()
            ->with('category')
            ->whereNull('expense_item_id')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->orderByDesc('expense_date')
            ->get();

        // (b) Item yang tercatat di lebih dari satu kategori (seluruh riwayat).
        $multiCategory = Expense::query()
            ->whereNotNull('expense_item_id')
            ->select('expense_item_id')
            ->selectRaw('COUNT(DISTINCT expense_category_id) as category_count')
            ->groupBy('expense_item_id')
            ->havingRaw('COUNT(DISTINCT expense_category_id) > 1')
            ->with('item:id,name')
            ->get()
            ->map(function ($row) {
                $categories = Expense::query()
                    ->where('expense_item_id', $row->expense_item_id)
                    ->with('category:id,name')
                    ->get()
                    ->pluck('category.name')
                    ->filter()
                    ->unique()
                    ->values();

                return (object) [
                    'item' => $row->item?->name ?? ('Item #'.$row->expense_item_id),
                    'categories' => $categories->join(', '),
                    'category_count' => (int) $row->category_count,
                ];
            });

        // (c) Kategori fixed naik >30% dari rata-rata 3 bulan sebelumnya.
        $periodStart = Carbon::parse($dateFrom)->startOfMonth();
        $prevStart = $periodStart->copy()->subMonths(3);
        $prevEnd = $periodStart->copy()->subDay();

        $fixedSpikes = ExpenseCategory::query()
            ->where('cost_behavior', ExpenseCategory::COST_FIXED)
            ->orderBy('name')
            ->get()
            ->map(function (ExpenseCategory $category) use ($dateFrom, $dateTo, $prevStart, $prevEnd) {
                $current = (int) Expense::query()
                    ->where('expense_category_id', $category->id)
                    ->whereDate('expense_date', '>=', $dateFrom)
                    ->whereDate('expense_date', '<=', $dateTo)
                    ->sum('amount');

                $prevTotal = (int) Expense::query()
                    ->where('expense_category_id', $category->id)
                    ->whereDate('expense_date', '>=', $prevStart->toDateString())
                    ->whereDate('expense_date', '<=', $prevEnd->toDateString())
                    ->sum('amount');
                $prevAvg = (int) round($prevTotal / 3);

                $pct = $prevAvg > 0 ? round((($current - $prevAvg) / $prevAvg) * 100, 1) : null;

                return (object) [
                    'name' => $category->name,
                    'current' => $current,
                    'prev_avg' => $prevAvg,
                    'pct' => $pct,
                    'is_spike' => $prevAvg > 0 && $current > $prevAvg * 1.3,
                ];
            })
            ->filter(fn ($row) => $row->is_spike)
            ->values();

        return view('reports.review', [
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'withoutItem' => $withoutItem,
            'multiCategory' => $multiCategory,
            'fixedSpikes' => $fixedSpikes,
        ]);
    }

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
