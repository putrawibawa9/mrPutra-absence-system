<?php

namespace App\Support;

class WeeklyDay
{
    public const MONDAY = 'monday';
    public const TUESDAY = 'tuesday';
    public const WEDNESDAY = 'wednesday';
    public const THURSDAY = 'thursday';
    public const FRIDAY = 'friday';
    public const SATURDAY = 'saturday';
    public const SUNDAY = 'sunday';

    public static function options(): array
    {
        return [
            self::MONDAY => 'Senin',
            self::TUESDAY => 'Selasa',
            self::WEDNESDAY => 'Rabu',
            self::THURSDAY => 'Kamis',
            self::FRIDAY => 'Jumat',
            self::SATURDAY => 'Sabtu',
            self::SUNDAY => 'Minggu',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $value): string
    {
        return self::options()[$value] ?? (string) $value;
    }

    public static function sortOrder(?string $value): int
    {
        $order = array_flip(self::values());

        return $order[$value] ?? PHP_INT_MAX;
    }
}
