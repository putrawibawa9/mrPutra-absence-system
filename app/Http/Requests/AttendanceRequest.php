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
            'group_title' => ['nullable', 'string', 'max:255'],
            'student_ids' => ['nullable', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('is_active', true)],
            'date' => ['required', 'date'],
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

                    if (! $this->filled('payment_id')) {
                        $validator->errors()->add('payment_id', 'Please select an active payment.');

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

                $studentIds = collect($this->input('student_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($studentIds->isEmpty()) {
                    $validator->errors()->add('student_ids', 'Please select at least one student who is present.');

                    return;
                }

                $students = Student::query()
                    ->with('latestActivePayment')
                    ->whereIn('id', $studentIds)
                    ->get()
                    ->keyBy('id');

                foreach ($studentIds as $studentId) {
                    $student = $students->get($studentId);

                    if (! $student || ! $student->latestActivePayment || $student->latestActivePayment->remaining_sessions <= 0) {
                        $validator->errors()->add('student_ids', 'Every selected student must have an active payment with remaining sessions.');
                        break;
                    }
                }
            },
        ];
    }
}
