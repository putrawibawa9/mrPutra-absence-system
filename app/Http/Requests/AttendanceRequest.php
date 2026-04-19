<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttendanceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', 'single'),
            'teaching_minutes' => $this->input('teaching_minutes', 60),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['single', 'group'])],
            'student_id' => ['nullable', Rule::exists('students', 'id')->where('is_active', true)],
            'payment_id' => ['nullable', 'exists:payments,id'],
            'teacher_ids' => ['nullable', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', \App\Models\User::ROLE_TEACHER)],
            'group_title' => ['nullable', 'string', 'max:255'],
            'group_teacher_ids' => ['nullable', 'array', 'min:1'],
            'group_teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', \App\Models\User::ROLE_TEACHER)],
            'student_ids' => ['nullable', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('is_active', true)],
            'date' => ['required', 'date'],
            'teaching_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'learning_journal' => ['required', 'string', 'min:3'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('mode') === 'single') {
                    if (! $this->filled('student_id')) {
                        $validator->errors()->add('student_id', 'Please select a student.');

                        return;
                    }

                    $teacherIds = collect($this->input('teacher_ids', []))
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    if ($teacherIds->isEmpty()) {
                        $validator->errors()->add('teacher_ids', 'Please select at least one teacher for this attendance.');
                    }

                    if (! $this->filled('payment_id')) {
                        return;
                    }

                    $payment = Payment::query()->find($this->integer('payment_id'));

                    if (! $payment || $payment->student_id !== $this->integer('student_id')) {
                        $validator->errors()->add('payment_id', 'The selected payment does not belong to this student.');

                        return;
                    }

                    if ($payment->remaining_sessions <= 0) {
                        $validator->errors()->add('payment_id', 'The selected payment has no remaining sessions.');
                    }

                    return;
                }

                if (! $this->filled('group_title')) {
                    $validator->errors()->add('group_title', 'Please fill the class or session name.');
                }

                $teacherIds = collect($this->input('group_teacher_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($teacherIds->isEmpty()) {
                    $validator->errors()->add('group_teacher_ids', 'Please select at least one teacher for this class.');
                }

                $studentIds = collect($this->input('student_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($studentIds->isEmpty()) {
                    $validator->errors()->add('student_ids', 'Please select at least one student who is present.');

                    return;
                }

                $activeStudentCount = Student::query()
                    ->active()
                    ->whereIn('id', $studentIds)
                    ->count();

                if ($activeStudentCount !== $studentIds->count()) {
                    $validator->errors()->add('student_ids', 'Every selected student must be active.');
                }
            },
        ];
    }
}
