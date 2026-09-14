<?php

namespace App\Http\Requests;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('expense_category')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($categoryId)],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'cost_behavior' => ['nullable', Rule::in([ExpenseCategory::COST_FIXED, ExpenseCategory::COST_VARIABLE])],
        ];
    }
}
