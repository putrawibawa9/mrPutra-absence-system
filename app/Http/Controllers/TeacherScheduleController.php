<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherScheduleRequest;
use App\Models\Classroom;
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
            ->with(['teacher', 'coTeacher', 'classroom.students'])
            ->when($teacherId, fn ($query) => $query->where(fn ($q) => $q->where('teacher_id', $teacherId)->orWhere('co_teacher_id', $teacherId)));

        $allSchedules = (clone $base)
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->get();

        // Dikelompokkan per kelas/grup (identitas = nama siswa) sebagai kartu.
        $classroomCards = $allSchedules
            ->groupBy(fn ($schedule) => $schedule->classroom_id ?? 'none')
            ->map(fn ($items) => (object) [
                'classroom' => $items->first()->classroom,
                'title' => $items->first()->classroom?->name ?? ($items->first()->title ?: 'Tanpa kelas'),
                'student_hint' => $items->first()->classroom
                    ? $items->first()->classroom->studentHint()
                    : ($items->first()->title ?: 'Tanpa kelas'),
                'teacher_names' => $items->flatMap(fn ($schedule) => array_filter([
                    $schedule->teacher?->name,
                    $schedule->coTeacher ? $schedule->coTeacher->name.' (co)' : null,
                ]))->unique()->values()->join(', ') ?: '-',
                'slots' => $items->values(),
            ])
            ->sortBy(fn ($card) => $card->classroom?->name ?? 'zzz')
            ->values();

        $calendar = $this->buildCalendar(
            (clone $base)->orderBy('start_time')->get(),
            'teacher_id'
        );
        $legend = $teachers->pluck('name', 'id')->all();

        // Grup (kelas aktif) yang belum punya jadwal aktif — untuk monitoring.
        $scheduledClassroomIds = TeacherSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->pluck('classroom_id')
            ->all();

        $unscheduledGroups = Classroom::query()
            ->active()
            ->with('students:id,name')
            ->whereNotIn('id', $scheduledClassroomIds)
            ->orderBy('name')
            ->get();

        return view('teacher-schedules.index', compact('classroomCards', 'teachers', 'teacherId', 'calendar', 'legend', 'unscheduledGroups'));
    }

    /**
     * Susun jadwal mingguan jadi data kalender (grid hari x jam), lengkap dengan
     * peletakan blok yang bertumpuk berdampingan (seperti Google Calendar).
     */
    private function buildCalendar(Collection $schedules, string $colorKey = 'teacher_id'): array
    {
        $palette = ['#2563eb', '#16a34a', '#d97706', '#db2777', '#7c3aed', '#0891b2', '#dc2626', '#4f46e5', '#ca8a04', '#0d9488'];
        $colors = [];
        foreach ($schedules->pluck($colorKey)->filter()->unique()->values() as $index => $key) {
            $colors[$key] = $palette[$index % count($palette)];
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

            $flush = function () use (&$cluster, &$events, $startMin, $hourHeight, $colors, $colorKey): void {
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
                        'color' => $colors[$item['s']->{$colorKey}] ?? '#334155',
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

    /**
     * Kelas yang les hari ini (dari jadwal mingguan), untuk admin remind manual
     * lewat WA grup. Diurutkan dari jam paling awal.
     */
    public function today()
    {
        $todayKey = strtolower(now()->englishDayOfWeek);

        $schedules = TeacherSchedule::query()
            ->with(['teacher', 'classroom.students'])
            ->where('is_active', true)
            ->where('day_of_week', $todayKey)
            ->orderBy('start_time')
            ->get();

        return view('teacher-schedules.today', [
            'schedules' => $schedules,
            'dayLabel' => WeeklyDay::label($todayKey),
        ]);
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
        TeacherSchedule::create($this->scheduleData($request));

        return redirect()->route('teacher-schedules.index')->with('status', 'Jadwal guru berhasil ditambahkan.');
    }

    /**
     * Data jadwal dari request + judul otomatis dari nama kelas (buat tampilan).
     */
    private function scheduleData(TeacherScheduleRequest $request): array
    {
        $data = $request->validated();
        $data['title'] = Classroom::whereKey($data['classroom_id'])->value('name');
        // Normalisasi: string kosong dari select → null.
        $data['co_teacher_id'] = $request->integer('co_teacher_id') ?: null;

        return $data;
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
        $teacher_schedule->update($this->scheduleData($request));

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

        // Jadwal di mana guru ini berperan sebagai guru utama ATAU co-teacher.
        $schedules = TeacherSchedule::query()
            ->with(['classroom.students', 'teacher:id,name'])
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('teacher_id', $teacher->id)->orWhere('co_teacher_id', $teacher->id))
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->get();

        // Tandai peran guru pada tiap slot untuk ditampilkan.
        $schedules->each(fn (TeacherSchedule $schedule) => $schedule->setAttribute(
            'my_role',
            (int) $schedule->teacher_id === (int) $teacher->id ? 'teacher' : 'co_teacher',
        ));

        $groupedSchedules = $this->groupByDay($schedules);
        $calendar = $this->buildCalendar($schedules, 'classroom_id');

        $legend = [];
        foreach ($schedules as $schedule) {
            if ($schedule->classroom && ! isset($legend[$schedule->classroom_id])) {
                $legend[$schedule->classroom_id] = $schedule->classroom->nameWithStudentHint();
            }
        }

        return view('teacher-schedules.my', compact('groupedSchedules', 'calendar', 'legend'));
    }

    protected function formData(): array
    {
        return [
            'teachers' => User::teachers()->active()->orderBy('name')->get(),
            'classrooms' => Classroom::query()->active()->with('students:id,name')->orderBy('name')->get(),
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
