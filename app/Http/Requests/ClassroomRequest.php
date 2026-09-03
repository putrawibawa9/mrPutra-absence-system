<?php

namespace App\Http\Requests;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'division' => ['required', Rule::in(array_keys(Classroom::divisionOptions()))],
            'format' => ['required', Rule::in(array_keys(Classroom::formatOptions()))],
            'learning_mode' => ['nullable', Rule::in(array_keys(Classroom::learningModeOptions()))],
            'age_group' => ['required', Rule::in(array_keys(Classroom::ageOptions()))],
            'level' => ['nullable', Rule::in(array_keys(Classroom::levelOptions()))],
            'is_active' => ['required', 'boolean'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $studentIds = collect($this->input('student_ids', []))->filter()->map(fn ($id) => (int) $id)->unique();

            if ($this->input('format') === Classroom::FORMAT_PRIVATE && $studentIds->count() !== 1) {
                $validator->errors()->add('student_ids', 'Format Private hanya boleh berisi tepat 1 murid.');
            }

            // Satu murid hanya boleh berada di satu kelas aktif.
            $currentId = $this->route('classroom')?->id;
            $conflicts = Classroom::query()
                ->where('is_active', true)
                ->when($currentId, fn ($query) => $query->whereKeyNot($currentId))
                ->whereHas('students', fn ($query) => $query->whereIn('students.id', $studentIds->all()))
                ->with(['students' => fn ($query) => $query->whereIn('students.id', $studentIds->all())])
                ->get();

            $taken = [];
            foreach ($conflicts as $conflict) {
                foreach ($conflict->students as $student) {
                    $taken[$student->name] = $conflict->name;
                }
            }

            if ($taken !== []) {
                $list = collect($taken)->map(fn ($className, $name) => $name.' (kelas "'.$className.'")')->values()->join(', ');
                $validator->errors()->add('student_ids', 'Murid berikut sudah punya kelas aktif: '.$list.'. Keluarkan dulu dari kelas lama.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Pilih minimal satu murid untuk kelas ini.',
        ];
    }
}
