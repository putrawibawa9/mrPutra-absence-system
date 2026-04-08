<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', Rule::exists('students', 'id')->where('is_active', true)],
            'source_type' => ['required', Rule::in([Payment::SOURCE_PACKAGE, Payment::SOURCE_MANUAL])],
            'package_id' => ['nullable', 'exists:packages,id'],
            'manual_total_sessions' => ['nullable', 'integer', 'min:1'],
            'manual_remaining_sessions' => ['nullable', 'integer', 'min:0'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('source_type') === Payment::SOURCE_PACKAGE && ! $this->filled('package_id')) {
                    $validator->errors()->add('package_id', 'Please select a package.');
                }

                if ($this->input('source_type') === Payment::SOURCE_MANUAL) {
                    $total = (int) $this->input('manual_total_sessions');
                    $remaining = (int) $this->input('manual_remaining_sessions');

                    if (! $this->filled('manual_total_sessions')) {
                        $validator->errors()->add('manual_total_sessions', 'Please fill the total sessions for manual balance.');
                    }

                    if (! $this->filled('manual_remaining_sessions')) {
                        $validator->errors()->add('manual_remaining_sessions', 'Please fill the remaining sessions for manual balance.');
                    }

                    if ($this->filled('manual_total_sessions') && $this->filled('manual_remaining_sessions') && $remaining > $total) {
                        $validator->errors()->add('manual_remaining_sessions', 'Remaining sessions cannot be greater than total sessions.');
                    }
                }
            },
        ];
    }
}
