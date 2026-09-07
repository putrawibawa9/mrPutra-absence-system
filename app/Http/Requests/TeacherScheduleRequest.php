<?php

namespace App\Http\Requests;

use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TeacherScheduleRequest extends FormRequest
{
    /** Durasi tetap satu pertemuan (menit). Jam selesai = jam mulai + durasi ini. */
    public const SESSION_MINUTES = 70;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Jam selesai tidak diisi manual — otomatis jam mulai + 70 menit.
     */
    protected function prepareForValidation(): void
    {
        $start = $this->string('start_time')->toString();

        if (preg_match('/^\d{2}:\d{2}$/', $start)) {
            $this->merge([
                'end_time' => Carbon::createFromFormat('H:i', $start)
                    ->addMinutes(self::SESSION_MINUTES)
                    ->format('H:i'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_TEACHER))],
            'classroom_id' => ['required', Rule::exists('classrooms', 'id')],
            'day_of_week' => ['required', Rule::in(WeeklyDay::values())],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'classroom_id.required' => 'Pilih kelas untuk jadwal ini.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $teacherId = (int) $this->integer('teacher_id');
            $schedule = $this->route('teacher_schedule');
            $startTime = $this->string('start_time')->toString().':00';
            $endTime = $this->string('end_time')->toString().':00';
            $dayOfWeek = $this->string('day_of_week')->toString();

            // NOTE: Validasi bentrok jadwal dinonaktifkan sementara atas permintaan.
            // Untuk mengaktifkan kembali, hapus tanda komentar blok di bawah ini.
            // if ($this->boolean('is_active')) {
            //     $hasConflict = TeacherSchedule::query()
            //         ->where('teacher_id', $teacherId)
            //         ->where('day_of_week', $dayOfWeek)
            //         ->where('is_active', true)
            //         ->where('start_time', '<', $endTime)
            //         ->where('end_time', '>', $startTime)
            //         ->when($schedule, fn ($query) => $query->whereKeyNot($schedule->id))
            //         ->exists();
            //
            //     if ($hasConflict) {
            //         $validator->errors()->add('start_time', 'Jadwal guru bentrok dengan jadwal lain pada hari dan jam yang sama.');
            //     }
            // }

            $hasActiveAvailability = TeacherAvailability::query()
                ->where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->where('status', TeacherAvailability::STATUS_AVAILABLE)
                ->exists();

            if (! $hasActiveAvailability || ! $this->boolean('is_active')) {
                return;
            }

            $coveredByAvailability = TeacherAvailability::query()
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('status', TeacherAvailability::STATUS_AVAILABLE)
                ->where('start_time', '<=', $startTime)
                ->where('end_time', '>=', $endTime)
                ->exists();

            if (! $coveredByAvailability) {
                $validator->errors()->add('start_time', 'Jadwal hanya bisa dibuat di dalam blok ketersediaan guru yang aktif.');
            }
        });
    }
}
