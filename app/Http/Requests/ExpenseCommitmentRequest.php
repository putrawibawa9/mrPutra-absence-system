<?php

namespace App\Http\Requests;

use App\Models\ExpenseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExpenseCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_item_id' => ['required', 'integer', 'exists:expense_items,id'],
            'name' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'integer', 'min:1'],
            'months' => ['required', 'integer', 'min:2', 'max:60'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $itemId = $this->input('expense_item_id');

            if (! $itemId) {
                return;
            }

            $item = ExpenseItem::query()->with('category')->find($itemId);

            if ($item && ! $item->is_active) {
                $validator->errors()->add('expense_item_id', 'Item ini sudah tidak aktif.');
            }

            if ($item && $item->category?->isSystem()) {
                $validator->errors()->add('expense_item_id', 'Item kategori sistem tidak bisa dibuat komitmen.');
            }
        });
    }
}
