<?php

namespace App\Http\Requests;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExpenseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('expense_item')?->id;

        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_items', 'name')->ignore($itemId)],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'default_amount' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $categoryId = $this->input('expense_category_id');

            if (! $categoryId) {
                return;
            }

            $category = ExpenseCategory::query()->find($categoryId);

            // Item tidak boleh dibuat di kategori sistem (Fee Guru dikelola otomatis).
            if ($category?->isSystem()) {
                $validator->errors()->add('expense_category_id', 'Tidak bisa membuat item di kategori sistem (mis. Fee Guru).');
            }
        });
    }
}
