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
            'source_type' => ['required', Rule::in([Payment::SOURCE_PACKAGE, Payment::SOURCE_MANUAL, Payment::SOURCE_BOOK])],
            'package_id' => ['nullable', 'exists:packages,id'],
            'book_title' => ['nullable', 'string', 'max:255'],
            'book_price' => ['nullable', 'integer', 'min:1'],
            'initial_paid_amount' => ['nullable', 'integer', 'min:0'],
            'manual_total_sessions' => ['nullable', 'integer', 'min:1'],
            'manual_remaining_sessions' => ['nullable', 'integer', 'min:0'],
            'manual_price' => ['nullable', 'integer', 'min:0'],
            'payment_date' => ['required', 'date'],
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

                if ($this->input('source_type') === Payment::SOURCE_PACKAGE) {
                    if (! $this->filled('package_id')) {
                        $validator->errors()->add('package_id', 'Please select a package.');

                        return;
                    }

                    $packagePrice = (int) \App\Models\Package::query()
                        ->whereKey($this->integer('package_id'))
                        ->value('price');

                    $initialPaidAmount = $this->filled('initial_paid_amount')
                        ? (int) $this->input('initial_paid_amount')
                        : $packagePrice;

                    if ($initialPaidAmount > $packagePrice) {
                        $validator->errors()->add('initial_paid_amount', 'Initial paid amount cannot be greater than package price.');
                    }
                }

                if ($this->input('source_type') === Payment::SOURCE_BOOK) {
                    if (! $this->filled('book_title')) {
                        $validator->errors()->add('book_title', 'Please enter the book or module name.');
                    }

                    if (! $this->filled('book_price')) {
                        $validator->errors()->add('book_price', 'Please enter the book or module price.');

                        return;
                    }

                    $bookPrice = (int) $this->input('book_price');
                    $initialPaidAmount = $this->filled('initial_paid_amount')
                        ? (int) $this->input('initial_paid_amount')
                        : $bookPrice;

                    if ($initialPaidAmount > $bookPrice) {
                        $validator->errors()->add('initial_paid_amount', 'Initial paid amount cannot be greater than book or module price.');
                    }
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
