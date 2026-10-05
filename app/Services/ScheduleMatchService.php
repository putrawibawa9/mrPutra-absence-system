<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Support\WeeklyDay;
use Illuminate\Support\Collection;

/**
 * Mencocokkan preferensi waktu murid (hari + slot pagi/siang/sore/malam dari
 * form pendaftaran) dengan JAM KOSONG BERSIH guru — yaitu ketersediaan yang
 * sudah dikurangi jadwal mengajar aktif. Guru yang sudah mengajar di jam itu
 * tidak lagi dianggap available.
 */
class ScheduleMatchService
{
    /** Peta slot preferensi murid ke rentang jam (menit dari tengah malam). */
    public const BUCKETS = [
        'pagi' => ['06:00', '11:00'],
        'siang' => ['11:00', '15:00'],
        'sore' => ['15:00', '18:00'],
        'malam' => ['18:00', '21:00'],
    ];

    /** Cache jadwal mengajar aktif per guru+hari (menit). */
    private ?array $bookedCache = null;

    /**
     * Ketersediaan guru aktif & berstatus available (dipreload sekali lalu
     * dipakai untuk banyak registrasi).
     */
    public function availableSlots(): Collection
    {
        return TeacherAvailability::query()
            ->with('teacher:id,name')
            ->whereHas('teacher', fn ($query) => $query->where('is_active', true))
            ->where('is_active', true)
            ->where('status', TeacherAvailability::STATUS_AVAILABLE)
            ->orderByRaw($this->dayOrderSql())
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  array<int, string>  $days     day keys (kosong = semua hari)
     * @param  array<int, string>  $buckets  slot keys (kosong = semua slot)
     * @param  Collection|null     $availabilities  preload; null = query sendiri
     * @return Collection<int, object>  per guru: {teacher, slots[]}
     */
    public function matchForPreferences(array $days, array $buckets, ?Collection $availabilities = null): Collection
    {
        $availabilities ??= $this->availableSlots();
        $booked = $this->bookedByTeacherDay();

        $dayFilter = ! empty($days) ? array_values($days) : WeeklyDay::values();
        $bucketFilter = ! empty($buckets) ? array_values($buckets) : array_keys(self::BUCKETS);

        $matchesByTeacher = [];

        foreach ($availabilities as $availability) {
            if (! in_array($availability->day_of_week, $dayFilter, true)) {
                continue;
            }

            // Jam kosong bersih = ketersediaan dikurangi jadwal mengajar aktif.
            $blocks = $booked[$availability->teacher_id][$availability->day_of_week] ?? [];
            $free = $this->subtractIntervals(
                $this->toMinutes($availability->start_time),
                $this->toMinutes($availability->end_time),
                $blocks,
            );

            if ($free === []) {
                continue; // sudah penuh mengajar
            }

            $coveredBuckets = [];
            foreach ($bucketFilter as $bucketKey) {
                $range = self::BUCKETS[$bucketKey] ?? null;
                if (! $range) {
                    continue;
                }

                [$bucketStart, $bucketEnd] = [$this->toMinutes($range[0]), $this->toMinutes($range[1])];

                foreach ($free as [$fs, $fe]) {
                    if ($fs < $bucketEnd && $fe > $bucketStart) {
                        $coveredBuckets[] = $bucketKey;
                        break;
                    }
                }
            }

            if ($coveredBuckets === []) {
                continue;
            }

            $teacherId = $availability->teacher_id;
            if (! isset($matchesByTeacher[$teacherId])) {
                $matchesByTeacher[$teacherId] = (object) [
                    'teacher' => $availability->teacher,
                    'teacher_id' => $teacherId,
                    'slots' => [],
                ];
            }

            $matchesByTeacher[$teacherId]->slots[] = (object) [
                'day' => $availability->day_of_week,
                'day_label' => WeeklyDay::label($availability->day_of_week),
                'time_label' => $this->labelIntervals($free),
                'buckets' => $coveredBuckets,
                'bucket_label' => collect($coveredBuckets)
                    ->map(fn ($bucket) => Registration::timeOptions()[$bucket] ?? $bucket)
                    ->join(', '),
                'sort' => WeeklyDay::sortOrder($availability->day_of_week) * 10000 + $free[0][0],
            ];
        }

        return collect($matchesByTeacher)
            ->map(function (object $match) {
                $match->slots = collect($match->slots)->sortBy('sort')->values()->all();
                $match->slot_count = count($match->slots);

                return $match;
            })
            ->sortByDesc('slot_count')
            ->values();
    }

    /**
     * Cocokkan langsung dari sebuah registrasi.
     */
    public function matchForRegistration(Registration $registration, ?Collection $availabilities = null): Collection
    {
        return $this->matchForPreferences(
            $registration->available_days ?? [],
            $registration->time_preferences ?? [],
            $availabilities,
        );
    }

    /**
     * Cari guru yang JAM KOSONG bersihnya menutup satu hari & (opsional) rentang
     * jam tertentu — dipakai untuk merekomendasikan pengganti guru yang libur.
     *
     * @param  string       $dayKey            day key (mis. 'monday')
     * @param  string|null  $start,$end        jam; null = sepanjang hari itu
     * @param  array<int>   $excludeTeacherIds guru yang dikecualikan (yg libur / tidak tersedia)
     * @return Collection<int, object>
     */
    public function matchForDayTime(string $dayKey, ?string $start, ?string $end, ?Collection $availabilities = null, array $excludeTeacherIds = []): Collection
    {
        $availabilities ??= $this->availableSlots();
        $booked = $this->bookedByTeacherDay();
        $exclude = array_map('intval', $excludeTeacherIds);
        $wholeDay = blank($start) || blank($end);
        $windowStart = $wholeDay ? null : $this->toMinutes($start);
        $windowEnd = $wholeDay ? null : $this->toMinutes($end);

        $matchesByTeacher = [];

        foreach ($availabilities as $availability) {
            if ($availability->day_of_week !== $dayKey) {
                continue;
            }
            if (in_array((int) $availability->teacher_id, $exclude, true)) {
                continue;
            }

            $blocks = $booked[$availability->teacher_id][$availability->day_of_week] ?? [];
            $free = $this->subtractIntervals(
                $this->toMinutes($availability->start_time),
                $this->toMinutes($availability->end_time),
                $blocks,
            );

            if ($free === []) {
                continue; // sudah penuh mengajar
            }

            // Bila diminta rentang jam tertentu, hanya window bebas yang beririsan.
            $relevant = $wholeDay
                ? $free
                : array_values(array_filter($free, fn ($iv) => $iv[0] < $windowEnd && $iv[1] > $windowStart));

            if ($relevant === []) {
                continue;
            }

            $teacherId = (int) $availability->teacher_id;
            if (! isset($matchesByTeacher[$teacherId])) {
                $matchesByTeacher[$teacherId] = (object) [
                    'teacher' => $availability->teacher,
                    'teacher_id' => $teacherId,
                    'slots' => [],
                ];
            }

            $matchesByTeacher[$teacherId]->slots[] = (object) [
                'day_label' => WeeklyDay::label($availability->day_of_week),
                'time_label' => $this->labelIntervals($relevant),
                'sort' => $relevant[0][0],
            ];
        }

        return collect($matchesByTeacher)
            ->map(function (object $match) {
                $match->slots = collect($match->slots)->sortBy('sort')->values()->all();
                $match->slot_count = count($match->slots);

                return $match;
            })
            ->sortByDesc('slot_count')
            ->values();
    }

    /**
     * Jadwal mengajar aktif per guru+hari sebagai daftar [startMin, endMin].
     *
     * @return array<int, array<string, array<int, array{0:int,1:int}>>>
     */
    private function bookedByTeacherDay(): array
    {
        if ($this->bookedCache !== null) {
            return $this->bookedCache;
        }

        $this->bookedCache = [];

        TeacherSchedule::query()
            ->where('is_active', true)
            ->get(['teacher_id', 'co_teacher_id', 'day_of_week', 'start_time', 'end_time'])
            ->each(function (TeacherSchedule $schedule): void {
                $block = [
                    $this->toMinutes($schedule->start_time),
                    $this->toMinutes($schedule->end_time),
                ];
                // Guru sibuk baik sebagai guru utama maupun co-teacher.
                if ($schedule->teacher_id) {
                    $this->bookedCache[$schedule->teacher_id][$schedule->day_of_week][] = $block;
                }
                if ($schedule->co_teacher_id) {
                    $this->bookedCache[$schedule->co_teacher_id][$schedule->day_of_week][] = $block;
                }
            });

        return $this->bookedCache;
    }

    /**
     * Kurangi rentang [start,end] dengan blok terpakai → sisa interval bebas.
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
                    $next[] = [$s, $e];

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

    private function dayOrderSql(): string
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
