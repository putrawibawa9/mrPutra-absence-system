<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherScheduleRequest;
use App\Models\MaterialLink;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TeacherScheduleController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = $request->integer('teacher_id') ?: null;
        $teachers = User::teachers()->orderBy('name')->get();

        $base = TeacherSchedule::query()
            ->with(['teacher', 'student', 'materialLink'])
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId));

        $schedules = (clone $base)
            ->orderBy('teacher_id')
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        $calendar = $this->buildCalendar(
            (clone $base)->orderBy('start_time')->get()
        );

        return view('teacher-schedules.index', compact('schedules', 'teachers', 'teacherId', 'calendar'));
    }

    /**
     * Susun jadwal mingguan jadi data kalender (grid hari x jam), lengkap dengan
     * peletakan blok yang bertumpuk berdampingan (seperti Google Calendar).
     */
    private function buildCalendar(Collection $schedules): array
    {
        $palette = ['#2563eb', '#16a34a', '#d97706', '#db2777', '#7c3aed', '#0891b2', '#dc2626', '#4f46e5', '#ca8a04', '#0d9488'];
        $colors = [];
        foreach ($schedules->pluck('teacher_id')->unique()->values() as $index => $teacherId) {
            $colors[$teacherId] = $palette[$index % count($palette)];
        }

        $toMinutes = function ($time): int {
            [$h, $m] = array_pad(explode(':', (string) $time), 2, '0');

            return ((int) $h) * 60 + (int) $m;
        };

        $minStart = $schedules->min(fn ($s) => $toMinutes($s->start_time));
        $maxEnd = $schedules->max(fn ($s) => $toMinutes($s->end_time));
        if ($minStart === null) {
            $minStart = 8 * 60;
            $maxEnd = 18 * 60;
        }
        $startMin = intdiv((int) $minStart, 60) * 60;
        $endMin = (int) (ceil($maxEnd / 60) * 60);
        if ($endMin <= $startMin) {
            $endMin = $startMin + 60;
        }

        $hourHeight = 56;

        $days = [];
        foreach (WeeklyDay::options() as $key => $label) {
            $items = $schedules
                ->filter(fn ($s) => $s->day_of_week === $key)
                ->map(fn ($s) => ['s' => $s, 'start' => $toMinutes($s->start_time), 'end' => $toMinutes($s->end_time)])
                ->sortBy([['start', 'asc'], ['end', 'asc']])
                ->values()
                ->all();

            $events = [];
            $cluster = [];
            $clusterMaxEnd = null;

            $flush = function () use (&$cluster, &$events, $startMin, $hourHeight, $colors): void {
                if ($cluster === []) {
                    return;
                }

                $columnEnds = [];
                foreach ($cluster as $k => $item) {
                    $placed = null;
                    foreach ($columnEnds as $ci => $lastEnd) {
                        if ($lastEnd <= $item['start']) {
                            $placed = $ci;
                            break;
                        }
                    }
                    if ($placed === null) {
                        $placed = count($columnEnds);
                    }
                    $columnEnds[$placed] = $item['end'];
                    $cluster[$k]['col'] = $placed;
                }

                $totalCols = count($columnEnds);
                foreach ($cluster as $item) {
                    $width = 100 / $totalCols;
                    $events[] = [
                        's' => $item['s'],
                        'top' => ($item['start'] - $startMin) / 60 * $hourHeight,
                        'height' => max(22, ($item['end'] - $item['start']) / 60 * $hourHeight),
                        'left' => $item['col'] * $width,
                        'width' => $width,
                        'color' => $colors[$item['s']->teacher_id] ?? '#334155',
                    ];
                }

                $cluster = [];
            };

            foreach ($items as $item) {
                if ($clusterMaxEnd !== null && $item['start'] >= $clusterMaxEnd) {
                    $flush();
                    $clusterMaxEnd = null;
                }
                $cluster[] = $item;
                $clusterMaxEnd = max($clusterMaxEnd ?? 0, $item['end']);
            }
            $flush();

            $days[] = ['key' => $key, 'label' => $label, 'events' => $events];
        }

        $hours = range(intdiv($startMin, 60), intdiv($endMin, 60));

        return [
            'startMin' => $startMin,
            'endMin' => $endMin,
            'hourHeight' => $hourHeight,
            'gridHeight' => ($endMin - $startMin) / 60 * $hourHeight,
            'hours' => $hours,
            'days' => $days,
            'colors' => $colors,
            'isEmpty' => $schedules->isEmpty(),
        ];
    }

    public function create()
    {
        return view('teacher-schedules.create', array_merge(
            $this->formData(),
            ['schedule' => null],
        ));
    }

    public function store(TeacherScheduleRequest $request)
    {
        TeacherSchedule::create($request->validated());

        return redirect()->route('teacher-schedules.index')->with('status', 'Jadwal guru berhasil ditambahkan.');
    }

    public function edit(TeacherSchedule $teacher_schedule)
    {
        return view('teacher-schedules.edit', array_merge(
            $this->formData(),
            ['schedule' => $teacher_schedule],
        ));
    }

    public function update(TeacherScheduleRequest $request, TeacherSchedule $teacher_schedule)
    {
        $teacher_schedule->update($request->validated());

        return redirect()->route('teacher-schedules.index')->with('status', 'Jadwal guru berhasil diperbarui.');
    }

    public function destroy(TeacherSchedule $teacher_schedule)
    {
        $teacher_schedule->delete();

        return redirect()->route('teacher-schedules.index')->with('status', 'Jadwal guru berhasil dihapus.');
    }

    public function mySchedule()
    {
        $teacher = auth()->user();
        $groupedSchedules = $this->groupByDay(
            $teacher->teacherSchedules()
                ->with(['student', 'materialLink'])
                ->where('is_active', true)
                ->orderByRaw($this->dayOrderSql())
                ->orderBy('start_time')
                ->get()
        );

        return view('teacher-schedules.my', compact('groupedSchedules'));
    }

    protected function formData(): array
    {
        return [
            'teachers' => User::teachers()->orderBy('name')->get(),
            'students' => Student::active()->orderBy('name')->get(),
            'materialLinks' => MaterialLink::query()->where('is_active', true)->orderBy('title')->get(),
            'dayOptions' => WeeklyDay::options(),
        ];
    }

    public static function groupByDay(Collection $items): Collection
    {
        $grouped = collect(WeeklyDay::values())->mapWithKeys(function ($day) {
            return [$day => collect()];
        });

        foreach ($items as $item) {
            $grouped[$item->day_of_week] = $grouped[$item->day_of_week]->push($item);
        }

        return $grouped->map(fn ($dayItems, $day) => (object) [
            'key' => $day,
            'label' => WeeklyDay::label($day),
            'items' => $dayItems->sortBy('start_time')->values(),
        ]);
    }

    protected function dayOrderSql(): string
    {
        return "CASE day_of_week
            WHEN 'monday' THEN 1
            WHEN 'tuesday' THEN 2
            WHEN 'wednesday' THEN 3
            WHEN 'thursday' THEN 4
            WHEN 'friday' THEN 5
            WHEN 'saturday' THEN 6
            WHEN 'sunday' THEN 7
            ELSE 8
        END";
    }
}
