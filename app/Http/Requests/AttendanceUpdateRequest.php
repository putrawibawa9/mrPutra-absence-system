<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttendanceUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'student_id' => ['required', Rule::exists('students', 'id')->where('is_active', true)],
            'payment_id' => ['nullable', 'exists:payments,id'],
            'teacher_ids' => ['nullable', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', \App\Models\User::ROLE_TEACHER)],
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

                /** @var Attendance $attendance */
                $attendance = $this->route('attendance');
                $studentId = $this->integer('student_id');
                $paymentId = $this->integer('payment_id');

                if (! Student::query()->active()->whereKey($studentId)->exists()) {
                    $validator->errors()->add('student_id', 'The selected student must be active.');

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

                if (! $paymentId) {
                    return;
                }

                $payment = Payment::query()->find($paymentId);

                if (! $payment || $payment->student_id !== $studentId) {
                    $validator->errors()->add('payment_id', 'The selected payment does not belong to this student.');

                    return;
                }

                if ($payment->id !== $attendance->payment_id && $payment->remaining_sessions <= 0) {
                    $validator->errors()->add('payment_id', 'The selected payment has no remaining sessions.');
                }
            },
        ];
    }
}
