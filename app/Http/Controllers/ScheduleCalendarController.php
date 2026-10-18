<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ScheduleOccurrenceService;
use App\Support\WeeklyDay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleCalendarController extends Controller
{
    public function __construct(protected ScheduleOccurrenceService $occurrences)
    {
    }

    public function index(Request $request)
    {
        // Minggu yang ditampilkan (Senin–Minggu). ?week=YYYY-MM-DD = anchor mana saja.
        $anchor = Carbon::today();
        if ($request->filled('week')) {
            try {
                $anchor = Carbon::parse((string) $request->input('week'));
            } catch (\Exception) {
                $anchor = Carbon::today();
            }
        }
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $occurrences = $this->occurrences->forRange($weekStart, $weekEnd)
            ->groupBy(fn ($o) => $o->date->toDateString());

        $days = [];
        for ($date = $weekStart->copy(); $date->lte($weekEnd); $date->addDay()) {
            $days[] = (object) [
                'date' => $date->copy(),
                'label' => WeeklyDay::label(strtolower($date->englishDayOfWeek)),
                'is_today' => $date->isToday(),
                'occurrences' => $occurrences->get($date->toDateString(), collect())->values(),
            ];
        }

        return view('schedule-calendar.index', [
            'days' => $days,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'teachers' => User::teachers()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
