<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherAvailabilityRequest;
use App\Models\TeacherAvailability;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Support\Collection;

class TeacherAvailabilityController extends Controller
{
    public function index()
    {
        $availabilities = TeacherAvailability::query()
            ->with('teacher')
            ->orderBy('teacher_id')
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->paginate(15);

        return view('teacher-availabilities.index', compact('availabilities'));
    }

    public function create()
    {
        return view('teacher-availabilities.create', array_merge(
            $this->formData(),
            ['availability' => null],
        ));
    }

    public function store(TeacherAvailabilityRequest $request)
    {
        TeacherAvailability::create($request->validated());

        return redirect()->route('teacher-availabilities.index')->with('status', 'Ketersediaan guru berhasil ditambahkan.');
    }

    public function edit(TeacherAvailability $teacher_availability)
    {
        return view('teacher-availabilities.edit', array_merge(
            $this->formData(),
            ['availability' => $teacher_availability],
        ));
    }

    public function update(TeacherAvailabilityRequest $request, TeacherAvailability $teacher_availability)
    {
        $teacher_availability->update($request->validated());

        return redirect()->route('teacher-availabilities.index')->with('status', 'Ketersediaan guru berhasil diperbarui.');
    }

    public function destroy(TeacherAvailability $teacher_availability)
    {
        $teacher_availability->delete();

        return redirect()->route('teacher-availabilities.index')->with('status', 'Ketersediaan guru berhasil dihapus.');
    }

    public function myIndex()
    {
        $teacher = auth()->user();
        $groupedAvailabilities = $this->groupByDay(
            $teacher->teacherAvailabilities()
                ->orderByRaw($this->dayOrderSql())
                ->orderBy('start_time')
                ->get()
        );

        return view('teacher-availabilities.my-index', compact('groupedAvailabilities'));
    }

    public function myCreate()
    {
        return view('teacher-availabilities.my-create', [
            'dayOptions' => WeeklyDay::options(),
            'statusOptions' => TeacherAvailability::statusOptions(),
            'availability' => null,
        ]);
    }

    public function myStore(TeacherAvailabilityRequest $request)
    {
        TeacherAvailability::create(array_merge(
            $request->validated(),
            ['teacher_id' => $request->user()->id],
        ));

        return redirect()->route('my-availability.index')->with('status', 'Ketersediaan berhasil ditambahkan.');
    }

    public function myEdit(TeacherAvailability $teacher_availability)
    {
        abort_unless($teacher_availability->teacher_id === auth()->id(), 403);

        return view('teacher-availabilities.my-edit', [
            'availability' => $teacher_availability,
            'dayOptions' => WeeklyDay::options(),
            'statusOptions' => TeacherAvailability::statusOptions(),
        ]);
    }

    public function myUpdate(TeacherAvailabilityRequest $request, TeacherAvailability $teacher_availability)
    {
        abort_unless($teacher_availability->teacher_id === $request->user()->id, 403);

        $teacher_availability->update(array_merge(
            $request->validated(),
            ['teacher_id' => $request->user()->id],
        ));

        return redirect()->route('my-availability.index')->with('status', 'Ketersediaan berhasil diperbarui.');
    }

    public function myDestroy(TeacherAvailability $teacher_availability)
    {
        abort_unless($teacher_availability->teacher_id === auth()->id(), 403);

        $teacher_availability->delete();

        return redirect()->route('my-availability.index')->with('status', 'Ketersediaan berhasil dihapus.');
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

    protected function formData(): array
    {
        return [
            'teachers' => User::teachers()->orderBy('name')->get(),
            'dayOptions' => WeeklyDay::options(),
            'statusOptions' => TeacherAvailability::statusOptions(),
        ];
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
