<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower((string) $this->input('username')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($teacher)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher)],
        ];
    }
}
