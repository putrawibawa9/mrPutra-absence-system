<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherScheduleRequest;
use App\Models\Classroom;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherScheduleController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = $request->integer('teacher_id') ?: null;
        $teachers = User::teachers()->orderBy('name')->get();

        // Filter kategori kelas.
        $division = array_key_exists((string) $request->input('division'), Classroom::divisionOptions()) ? $request->input('division') : null;
        $format = array_key_exists((string) $request->input('format'), Classroom::formatOptions()) ? $request->input('format') : null;
        $ageGroup = array_key_exists((string) $request->input('age_group'), Classroom::ageOptions()) ? $request->input('age_group') : null;

        $base = TeacherSchedule::query()
            ->with(['teacher', 'coTeacher', 'classroom.students'])
            ->when($teacherId, fn ($query) => $query->where(fn ($q) => $q->where('teacher_id', $teacherId)->orWhere('co_teacher_id', $teacherId)))
            ->when($division, fn ($query) => $query->whereHas('classroom', fn ($c) => $c->where('division', $division)))
            ->when($format, fn ($query) => $query->whereHas('classroom', fn ($c) => $c->where('format', $format)))
            ->when($ageGroup, fn ($query) => $query->whereHas('classroom', fn ($c) => $c->where('age_group', $ageGroup)));

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
            ->when($division, fn ($query) => $query->where('division', $division))
            ->when($format, fn ($query) => $query->where('format', $format))
            ->when($ageGroup, fn ($query) => $query->where('age_group', $ageGroup))
            ->orderBy('name')
            ->get();

        return view('teacher-schedules.index', [
            'classroomCards' => $classroomCards,
            'teachers' => $teachers,
            'teacherId' => $teacherId,
            'calendar' => $calendar,
            'legend' => $legend,
            'unscheduledGroups' => $unscheduledGroups,
            'filters' => ['division' => $division, 'format' => $format, 'age_group' => $ageGroup],
            'divisionOptions' => Classroom::divisionOptions(),
            'formatOptions' => Classroom::formatOptions(),
            'ageOptions' => Classroom::ageOptions(),
        ]);
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

    /** Halaman upload CSV jadwal kelas. */
    public function importForm()
    {
        return view('teacher-schedules.import');
    }

    /**
     * Import jadwal kelas dari CSV (kolom: nama_kelas, hari, jam_mulai, jam_selesai).
     * Kelas yang belum ada dibuat otomatis; jadwal dibuat tanpa guru (di-assign nanti).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [], ['file' => 'file CSV']);

        $rows = [];
        if (($handle = fopen($request->file('file')->getRealPath(), 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'File kosong atau belum ada baris data.']);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $columns = ['nama_kelas', 'hari', 'jam_mulai', 'jam_selesai'];
        $idx = [];
        foreach ($columns as $col) {
            $pos = array_search($col, $header, true);
            if ($pos === false) {
                return back()->withErrors(['file' => "Kolom '{$col}' tidak ditemukan di baris header."]);
            }
            $idx[$col] = $pos;
        }

        $parsed = [];
        $rowErrors = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // baris kosong
            }

            $line = $i + 1;
            $name = trim((string) ($row[$idx['nama_kelas']] ?? ''));
            $dayRaw = trim((string) ($row[$idx['hari']] ?? ''));
            $start = $this->normalizeTime((string) ($row[$idx['jam_mulai']] ?? ''));
            $end = $this->normalizeTime((string) ($row[$idx['jam_selesai']] ?? ''));
            $day = WeeklyDay::fromLabel($dayRaw);

            if ($name === '') {
                $rowErrors[] = "Baris {$line}: nama_kelas kosong.";
            } elseif (! $day) {
                $rowErrors[] = "Baris {$line}: hari '{$dayRaw}' tidak dikenali (pakai Senin–Minggu).";
            } elseif (! $start || ! $end) {
                $rowErrors[] = "Baris {$line}: jam harus format HH:MM (24 jam).";
            } elseif ($start >= $end) {
                $rowErrors[] = "Baris {$line}: jam_mulai harus sebelum jam_selesai.";
            } else {
                $parsed[] = ['name' => $name, 'day' => $day, 'start' => $start, 'end' => $end];
            }
        }

        if ($rowErrors !== []) {
            return back()
                ->withErrors(['file' => 'Import dibatalkan, tidak ada data yang disimpan. Perbaiki baris berikut lalu unggah ulang.'])
                ->with('import_errors', $rowErrors);
        }

        if ($parsed === []) {
            return back()->withErrors(['file' => 'Tidak ada baris data yang bisa diimpor.']);
        }

        $createdClasses = 0;
        $createdSchedules = 0;
        $skipped = 0;

        DB::transaction(function () use ($parsed, &$createdClasses, &$createdSchedules, &$skipped): void {
            $classCache = [];

            foreach ($parsed as $p) {
                $key = mb_strtolower($p['name']);

                if (! isset($classCache[$key])) {
                    $classroom = Classroom::query()->whereRaw('LOWER(name) = ?', [$key])->first();
                    if (! $classroom) {
                        // Default kelas baru: English · Semi · Teens/Adult (Mr Putra Speak);
                        // bisa diubah admin lewat Edit kelas.
                        $classroom = Classroom::create([
                            'name' => $p['name'],
                            'division' => Classroom::DIVISION_ENGLISH,
                            'format' => Classroom::FORMAT_SEMI,
                            'age_group' => Classroom::AGE_TEENS_ADULT,
                            'is_active' => true,
                        ]);
                        $createdClasses++;
                    }
                    $classCache[$key] = $classroom;
                }
                $classroom = $classCache[$key];

                $exists = TeacherSchedule::query()
                    ->where('classroom_id', $classroom->id)
                    ->where('day_of_week', $p['day'])
                    ->where('start_time', $p['start'])
                    ->where('end_time', $p['end'])
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                TeacherSchedule::create([
                    'classroom_id' => $classroom->id,
                    'teacher_id' => null,
                    'title' => $p['name'],
                    'day_of_week' => $p['day'],
                    'start_time' => $p['start'],
                    'end_time' => $p['end'],
                    'is_active' => true,
                ]);
                $createdSchedules++;
            }
        });

        return redirect()->route('teacher-schedules.index')->with(
            'status',
            "Import selesai: {$createdSchedules} jadwal dibuat, {$createdClasses} kelas baru otomatis, {$skipped} baris dilewati (sudah ada). Assign guru lewat Edit jadwal."
        );
    }

    /** Validasi + normalisasi "H:MM"/"HH:MM" → "HH:MM"; null bila tidak valid. */
    private function normalizeTime(string $raw): ?string
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', trim($raw), $m)) {
            return null;
        }

        $hour = (int) $m[1];
        $minute = (int) $m[2];
        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
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
