<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherAvailabilityRequest;
use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Support\Collection;

class TeacherAvailabilityController extends Controller
{
    public function index()
    {
        $availabilities = TeacherAvailability::query()
            ->with('teacher')
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->get();

        // Jam mengajar yang sudah di-assign (aktif) per guru+hari, untuk dikurangkan
        // dari ketersediaan → jam kosong bersih. Dihitung baik sebagai GURU UTAMA
        // maupun CO-TEACHER (co_teacher_id), karena keduanya bikin guru itu sibuk.
        $bookedByTeacherDay = [];
        TeacherSchedule::query()
            ->where('is_active', true)
            ->get(['teacher_id', 'co_teacher_id', 'day_of_week', 'start_time', 'end_time'])
            ->each(function (TeacherSchedule $schedule) use (&$bookedByTeacherDay): void {
                $block = [$this->toMinutes($schedule->start_time), $this->toMinutes($schedule->end_time)];
                $bookedByTeacherDay[$schedule->teacher_id][$schedule->day_of_week][] = $block;
                if ($schedule->co_teacher_id) {
                    $bookedByTeacherDay[$schedule->co_teacher_id][$schedule->day_of_week][] = $block;
                }
            });

        // Anotasi tiap slot: jam terpakai ngajar & sisa jam bersih.
        $availabilities->each(function (TeacherAvailability $slot) use ($bookedByTeacherDay): void {
            $blocks = $bookedByTeacherDay[$slot->teacher_id][$slot->day_of_week] ?? [];

            $start = $this->toMinutes($slot->start_time);
            $end = $this->toMinutes($slot->end_time);

            $free = $this->subtractIntervals($start, $end, $blocks);
            $booked = $this->subtractIntervals($start, $end, $free); // komplemen = jam terpakai

            $slot->setAttribute('free_label', $this->labelIntervals($free));
            $slot->setAttribute('booked_label', $this->labelIntervals($booked));
            $slot->setAttribute('is_fully_booked', $free === []);
            $slot->setAttribute('has_booked', $booked !== []);
        });

        // Dikelompokkan per guru supaya tampil sebagai kartu yang mudah dibaca.
        $teacherCards = $availabilities
            ->groupBy('teacher_id')
            ->map(fn (Collection $items) => (object) [
                'teacher' => $items->first()->teacher,
                'slots' => $items->values(),
                'available_count' => $items->where('status', TeacherAvailability::STATUS_AVAILABLE)->count(),
                'unavailable_count' => $items->where('status', TeacherAvailability::STATUS_UNAVAILABLE)->count(),
            ])
            ->sortBy(fn ($card) => $card->teacher?->name ?? '')
            ->values();

        return view('teacher-availabilities.index', [
            'teacherCards' => $teacherCards,
            'totalSlots' => $availabilities->count(),
        ]);
    }

    /**
     * Kurangi rentang [start,end] dengan daftar blok terpakai. Hasil: sisa interval bebas.
     *
     * @param  array<int, array{0:int,1:int}>  $blocks
     * @return array<int, array{0:int,1:int}>
     */
    private function subtractIntervals(int $start, int $end, array $blocks): array
    {
        $free = [[$start, $end]];

        foreach ($blocks as [$b0, $b1]) {
            $next = [];
            foreach ($free as [$s, $e]) {
                if ($b1 <= $s || $b0 >= $e) {
                    $next[] = [$s, $e]; // tidak beririsan

                    continue;
                }
                if ($b0 > $s) {
                    $next[] = [$s, $b0];
                }
                if ($b1 < $e) {
                    $next[] = [$b1, $e];
                }
            }
            $free = $next;
        }

        return array_values(array_filter($free, fn ($iv) => $iv[1] > $iv[0]));
    }

    private function labelIntervals(array $intervals): string
    {
        if ($intervals === []) {
            return '-';
        }

        return collect($intervals)
            ->map(fn ($iv) => $this->fromMinutes($iv[0]).' - '.$this->fromMinutes($iv[1]))
            ->join(', ');
    }

    private function toMinutes($time): int
    {
        [$h, $m] = array_pad(explode(':', substr((string) $time, 0, 5)), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    private function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
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
            'teachers' => User::teachers()->active()->orderBy('name')->get(),
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
