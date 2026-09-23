<?php

namespace App\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Membangun payload sync pivot guru untuk satu pertemuan, memisahkan guru utama
 * (role 'teacher', fee standar) dan co-teacher/trainee (role 'co_teacher', fee
 * custom). Dipakai controller absensi kelas maupun edit attendance.
 */
trait SyncsSessionTeachers
{
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_CO_TEACHER = 'co_teacher';

    /**
     * Ambil input co-teacher dari request: array paralel co_teacher_id[] & co_teacher_fee[].
     * Mengembalikan [teacher_id => fee_amount(int>=0)], membuang id kosong, duplikat,
     * dan id yang sudah jadi guru utama.
     *
     * @return array<int, int>
     */
    protected function parseCoTeachers(Request $request, Collection $primaryTeacherIds): array
    {
        $ids = collect($request->input('co_teacher_id', []))->values();
        $fees = collect($request->input('co_teacher_fee', []))->values();

        $out = [];
        foreach ($ids as $i => $rawId) {
            $id = (int) $rawId;
            if ($id <= 0 || $primaryTeacherIds->contains($id) || isset($out[$id])) {
                continue;
            }

            $rawFee = $fees->get($i);
            $out[$id] = ($rawFee === null || $rawFee === '') ? 0 : max(0, (int) $rawFee);
        }

        return $out;
    }

    /**
     * Payload untuk BelongsToMany::sync() — guru utama fee null (pakai standar),
     * co-teacher fee custom. Guru utama menang bila id tumpang tindih.
     *
     * @param  array<int, int>  $coTeachers  [teacher_id => fee]
     * @return array<int, array{role: string, fee_amount: int|null}>
     */
    protected function teacherSyncPayload(Collection $primaryTeacherIds, array $coTeachers): array
    {
        $payload = [];

        foreach ($primaryTeacherIds as $id) {
            $payload[(int) $id] = ['role' => self::ROLE_TEACHER, 'fee_amount' => null];
        }

        foreach ($coTeachers as $id => $fee) {
            if (isset($payload[$id])) {
                continue; // sudah jadi guru utama
            }
            $payload[$id] = ['role' => self::ROLE_CO_TEACHER, 'fee_amount' => $fee];
        }

        return $payload;
    }
}
