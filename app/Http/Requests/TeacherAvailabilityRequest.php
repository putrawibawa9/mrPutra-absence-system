<?php

namespace App\Http\Requests;

use App\Models\TeacherAvailability;
use App\Models\User;
use App\Support\WeeklyDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_TEACHER))],
            'day_of_week' => ['required', Rule::in(WeeklyDay::values())],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(array_keys(TeacherAvailability::statusOptions()))],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->boolean('is_active')) {
                return;
            }

            $availability = $this->route('teacher_availability');
            $teacherId = (int) $this->integer('teacher_id');
            $dayOfWeek = $this->string('day_of_week')->toString();
            $startTime = $this->string('start_time')->toString().':00';
            $endTime = $this->string('end_time')->toString().':00';

            $hasOverlap = TeacherAvailability::query()
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->when($availability, fn ($query) => $query->whereKeyNot($availability->id))
                ->exists();

            if ($hasOverlap) {
                $validator->errors()->add('start_time', 'Blok ketersediaan bentrok dengan blok lain pada hari dan jam yang sama.');
            }
        });
    }
}
