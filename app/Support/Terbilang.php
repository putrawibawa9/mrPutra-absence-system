<?php

namespace App\Support;

/**
 * Konversi angka ke teks Bahasa Indonesia untuk kwitansi.
 * Contoh: 50000 -> "lima puluh ribu rupiah".
 */
class Terbilang
{
    public static function rupiah(int|float $amount): string
    {
        $words = preg_replace('/\s+/', ' ', trim(self::words((int) round($amount))));

        return ($words === '' ? 'nol' : $words).' rupiah';
    }

    private static function words(int $number): string
    {
        $number = abs($number);
        $units = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $units[$number];
        }

        if ($number < 20) {
            return self::words($number - 10).' belas';
        }

        if ($number < 100) {
            return self::words(intdiv($number, 10)).' puluh '.self::words($number % 10);
        }

        if ($number < 200) {
            return 'seratus '.self::words($number - 100);
        }

        if ($number < 1000) {
            return self::words(intdiv($number, 100)).' ratus '.self::words($number % 100);
        }

        if ($number < 2000) {
            return 'seribu '.self::words($number - 1000);
        }

        if ($number < 1_000_000) {
            return self::words(intdiv($number, 1000)).' ribu '.self::words($number % 1000);
        }

        if ($number < 1_000_000_000) {
            return self::words(intdiv($number, 1_000_000)).' juta '.self::words($number % 1_000_000);
        }

        if ($number < 1_000_000_000_000) {
            return self::words(intdiv($number, 1_000_000_000)).' miliar '.self::words($number % 1_000_000_000);
        }

        return self::words(intdiv($number, 1_000_000_000_000)).' triliun '.self::words($number % 1_000_000_000_000);
    }
}
