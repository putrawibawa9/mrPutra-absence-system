<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $moduleId = $this->route('learning_module')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('learning_modules', 'name')->ignore($moduleId)],
            'price' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
