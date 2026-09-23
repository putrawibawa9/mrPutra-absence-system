<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\TeacherAvailability;
use App\Support\WeeklyDay;
use Illuminate\Support\Collection;

/**
 * Mencocokkan preferensi waktu murid (hari + slot pagi/siang/sore/malam dari
 * form pendaftaran) dengan ketersediaan guru (hari + jam konkret). Mengembalikan
 * daftar guru yang cocok beserta slot bentroknya, supaya admin tak perlu manual.
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

    /**
     * Ketersediaan guru aktif & berstatus available (dipreload sekali lalu
     * dipakai untuk banyak registrasi).
     */
    public function availableSlots(): Collection
    {
        return TeacherAvailability::query()
            ->with('teacher:id,name')
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

        $dayFilter = ! empty($days) ? array_values($days) : WeeklyDay::values();
        $bucketFilter = ! empty($buckets) ? array_values($buckets) : array_keys(self::BUCKETS);

        $matchesByTeacher = [];

        foreach ($availabilities as $availability) {
            if (! in_array($availability->day_of_week, $dayFilter, true)) {
                continue;
            }

            $availStart = $this->toMinutes($availability->start_time);
            $availEnd = $this->toMinutes($availability->end_time);

            $coveredBuckets = [];
            foreach ($bucketFilter as $bucketKey) {
                $range = self::BUCKETS[$bucketKey] ?? null;
                if (! $range) {
                    continue;
                }

                [$bucketStart, $bucketEnd] = [$this->toMinutes($range[0]), $this->toMinutes($range[1])];

                // Overlap: mulai sebelum bucket selesai & selesai setelah bucket mulai.
                if ($availStart < $bucketEnd && $availEnd > $bucketStart) {
                    $coveredBuckets[] = $bucketKey;
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
                'time_label' => $availability->timeRangeLabel(),
                'buckets' => $coveredBuckets,
                'bucket_label' => collect($coveredBuckets)
                    ->map(fn ($bucket) => Registration::timeOptions()[$bucket] ?? $bucket)
                    ->join(', '),
                'sort' => WeeklyDay::sortOrder($availability->day_of_week) * 10000 + $availStart,
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

    private function toMinutes($time): int
    {
        [$h, $m] = array_pad(explode(':', substr((string) $time, 0, 5)), 2, '0');

        return ((int) $h) * 60 + (int) $m;
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
