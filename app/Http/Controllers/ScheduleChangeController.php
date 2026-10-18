<?php

namespace App\Http\Controllers;

use App\Models\ScheduleChange;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleChangeController extends Controller
{
    /** Tetapkan guru pengganti untuk 1 sesi di tanggal tertentu. */
    public function substitute(Request $request)
    {
        $data = $this->validateAnchor($request, [
            'substitute_teacher_id' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', User::ROLE_TEACHER))],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->upsert($request, [
            'substitute_teacher_id' => (int) $data['substitute_teacher_id'],
            'moved_to_date' => null,
            'moved_start_time' => null,
            'moved_end_time' => null,
            'is_cancelled' => false,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Guru pengganti ditetapkan untuk tanggal itu.');
    }

    /** Pindah sesi ke tanggal lain (sementara). */
    public function move(Request $request)
    {
        $data = $this->validateAnchor($request, [
            'moved_to_date' => ['required', 'date'],
            'moved_start_time' => ['nullable', 'date_format:H:i'],
            'moved_end_time' => ['nullable', 'date_format:H:i', 'after:moved_start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->upsert($request, [
            'substitute_teacher_id' => null,
            'moved_to_date' => $data['moved_to_date'],
            'moved_start_time' => $data['moved_start_time'] ?? null,
            'moved_end_time' => $data['moved_end_time'] ?? null,
            'is_cancelled' => false,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Sesi dipindah ke tanggal baru untuk sekali ini.');
    }

    /** Batalkan sesi di tanggal tertentu. */
    public function cancel(Request $request)
    {
        $data = $this->validateAnchor($request, [
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->upsert($request, [
            'substitute_teacher_id' => null,
            'moved_to_date' => null,
            'moved_start_time' => null,
            'moved_end_time' => null,
            'is_cancelled' => true,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Sesi dibatalkan untuk tanggal itu.');
    }

    /** Kembalikan ke jadwal normal (hapus pengecualian tanggal itu). */
    public function reset(Request $request)
    {
        $data = $this->validateAnchor($request);

        ScheduleChange::query()
            ->where('teacher_schedule_id', $data['teacher_schedule_id'])
            ->whereDate('date', $data['date'])
            ->delete();

        return back()->with('status', 'Jadwal dikembalikan ke normal.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function validateAnchor(Request $request, array $extra = []): array
    {
        return $request->validate(array_merge([
            'teacher_schedule_id' => ['required', Rule::exists('teacher_schedules', 'id')],
            'date' => ['required', 'date'],
        ], $extra));
    }

    /** @param array<string, mixed> $attributes */
    private function upsert(Request $request, array $attributes): void
    {
        ScheduleChange::query()->updateOrCreate(
            [
                'teacher_schedule_id' => (int) $request->integer('teacher_schedule_id'),
                'date' => $request->date('date')->toDateString(),
            ],
            array_merge($attributes, ['created_by_user_id' => $request->user()->id]),
        );
    }
}
