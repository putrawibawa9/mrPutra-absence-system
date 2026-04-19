<?php

namespace App\Http\Requests;

use App\Models\TeacherAvailability;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_TEACHER))],
            'student_id' => ['nullable', 'exists:students,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'day_of_week' => ['required', Rule::in(WeeklyDay::values())],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
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

            if ($this->boolean('is_active')) {
                $hasConflict = TeacherSchedule::query()
                    ->where('teacher_id', $teacherId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->when($schedule, fn ($query) => $query->whereKeyNot($schedule->id))
                    ->exists();

                if ($hasConflict) {
                    $validator->errors()->add('start_time', 'Jadwal guru bentrok dengan jadwal lain pada hari dan jam yang sama.');
                }
            }

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
