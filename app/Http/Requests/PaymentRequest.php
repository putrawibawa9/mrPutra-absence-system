<?php

namespace App\Http\Requests;

use App\Models\Classroom;
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
            'source_type' => ['required', Rule::in([Payment::SOURCE_TOKEN, Payment::SOURCE_BOOK])],
            'division' => ['nullable', Rule::in([Classroom::DIVISION_ENGLISH, Classroom::DIVISION_CODING])],
            'format' => ['nullable', Rule::in([Classroom::FORMAT_PRIVATE, Classroom::FORMAT_SEMI])],
            'total_sessions' => ['nullable', 'integer', 'min:1'],
            'price_amount' => ['nullable', 'integer', 'min:0'],
            'learning_module_id' => ['nullable', 'exists:learning_modules,id'],
            'book_title' => ['nullable', 'string', 'max:255'],
            'book_price' => ['nullable', 'integer', 'min:1'],
            'initial_paid_amount' => ['nullable', 'integer', 'min:0'],
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

                if ($this->input('source_type') === Payment::SOURCE_TOKEN) {
                    if (! $this->filled('total_sessions')) {
                        $validator->errors()->add('total_sessions', 'Isi jumlah token yang dibeli.');
                    }

                    if (! $this->filled('price_amount')) {
                        $validator->errors()->add('price_amount', 'Isi harga total.');

                        return;
                    }

                    $price = (int) $this->input('price_amount');
                    $initialPaidAmount = $this->filled('initial_paid_amount')
                        ? (int) $this->input('initial_paid_amount')
                        : $price;

                    if ($initialPaidAmount > $price) {
                        $validator->errors()->add('initial_paid_amount', 'Jumlah dibayar tidak boleh melebihi harga total.');
                    }
                }

                if ($this->input('source_type') === Payment::SOURCE_BOOK) {
                    $modulePrice = $this->filled('learning_module_id')
                        ? (int) \App\Models\LearningModule::query()
                            ->whereKey($this->integer('learning_module_id'))
                            ->value('price')
                        : null;

                    if (! $this->filled('learning_module_id') && ! $this->filled('book_title')) {
                        $validator->errors()->add('book_title', 'Isi nama buku atau modul.');
                    }

                    if (! $this->filled('learning_module_id') && ! $this->filled('book_price')) {
                        $validator->errors()->add('book_price', 'Isi harga buku atau modul.');

                        return;
                    }

                    $bookPrice = $modulePrice ?? (int) $this->input('book_price');
                    $initialPaidAmount = $this->filled('initial_paid_amount')
                        ? (int) $this->input('initial_paid_amount')
                        : $bookPrice;

                    if ($initialPaidAmount > $bookPrice) {
                        $validator->errors()->add('initial_paid_amount', 'Jumlah dibayar tidak boleh melebihi harga buku atau modul.');
                    }
                }
            },
        ];
    }
}
