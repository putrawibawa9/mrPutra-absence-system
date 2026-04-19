<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')->ignore($this->route('student'))],
            'book_info' => ['nullable', 'string', 'max:2000'],
            'program_type' => ['required', Rule::in(array_keys(Student::programOptions()))],
            'registration_date' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
