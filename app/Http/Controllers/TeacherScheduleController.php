<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherScheduleRequest;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Support\Collection;

class TeacherScheduleController extends Controller
{
    public function index()
    {
        $schedules = TeacherSchedule::query()
            ->with(['teacher', 'student'])
            ->orderBy('teacher_id')
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->paginate(15);

        return view('teacher-schedules.index', compact('schedules'));
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
                ->with('student')
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
