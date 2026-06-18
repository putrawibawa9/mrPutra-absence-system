<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttendanceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', 'single'),
            'teaching_minutes' => $this->input('teaching_minutes', 60),
            'group_journal_mode' => $this->input('group_journal_mode', 'group'),
            'group_homework_mode' => $this->input('group_homework_mode', 'group'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['single', 'group'])],
            'student_id' => ['nullable', Rule::exists('students', 'id')->where('is_active', true)],
            'payment_id' => ['nullable', 'exists:payments,id'],
            'material_link_ids' => ['nullable', 'array'],
            'material_link_ids.*' => ['integer', Rule::exists('material_links', 'id')->where('is_active', true)],
            'teacher_ids' => ['nullable', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', \App\Models\User::ROLE_TEACHER)],
            'group_title' => ['nullable', 'string', 'max:255'],
            'group_teacher_ids' => ['nullable', 'array', 'min:1'],
            'group_teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', \App\Models\User::ROLE_TEACHER)],
            'group_material_link_ids' => ['nullable', 'array'],
            'group_material_link_ids.*' => ['integer', Rule::exists('material_links', 'id')->where('is_active', true)],
            'group_journal_mode' => ['required', Rule::in(['group', 'per_student'])],
            'group_homework_mode' => ['required', Rule::in(['group', 'per_student'])],
            'student_ids' => ['nullable', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('is_active', true)],
            'date' => ['required', 'date'],
            'teaching_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'learning_journal' => ['nullable', 'string', 'min:3'],
            'homework_content' => ['nullable', 'string', 'min:3'],
            'group_homework_content' => ['nullable', 'string', 'min:3'],
            'student_learning_journals' => ['nullable', 'array'],
            'student_learning_journals.*' => ['nullable', 'string', 'min:3'],
            'student_homework_contents' => ['nullable', 'array'],
            'student_homework_contents.*' => ['nullable', 'string', 'min:3'],
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

                if ($this->input('mode') === 'single') {
                    if (! $this->filled('learning_journal')) {
                        $validator->errors()->add('learning_journal', 'Please fill the learning journal.');

                        return;
                    }

                    if (! $this->filled('student_id')) {
                        $validator->errors()->add('student_id', 'Please select a student.');

                        return;
                    }

                    $teacherIds = collect($this->input('teacher_ids', []))
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    if ($teacherIds->isEmpty()) {
                        $validator->errors()->add('teacher_ids', 'Please select at least one teacher for this attendance.');
                    }

                    if (! $this->filled('payment_id')) {
                        return;
                    }

                    $payment = Payment::query()->find($this->integer('payment_id'));

                    if (! $payment || $payment->student_id !== $this->integer('student_id')) {
                        $validator->errors()->add('payment_id', 'The selected payment does not belong to this student.');

                        return;
                    }

                    if ($payment->remaining_sessions <= 0) {
                        $validator->errors()->add('payment_id', 'The selected payment has no remaining sessions.');
                    }

                    return;
                }

                if (! $this->filled('group_title')) {
                    $validator->errors()->add('group_title', 'Please fill the class or session name.');
                }

                $teacherIds = collect($this->input('group_teacher_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($teacherIds->isEmpty()) {
                    $validator->errors()->add('group_teacher_ids', 'Please select at least one teacher for this class.');
                }

                $studentIds = collect($this->input('student_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($studentIds->isEmpty()) {
                    $validator->errors()->add('student_ids', 'Please select at least one student who is present.');

                    return;
                }

                $activeStudentCount = Student::query()
                    ->active()
                    ->whereIn('id', $studentIds)
                    ->count();

                if ($activeStudentCount !== $studentIds->count()) {
                    $validator->errors()->add('student_ids', 'Every selected student must be active.');
                }

                if ($this->input('group_journal_mode') === 'group') {
                    if (! $this->filled('learning_journal')) {
                        $validator->errors()->add('learning_journal', 'Please fill the group learning journal.');
                    }

                    return;
                }

                $studentLearningJournals = collect($this->input('student_learning_journals', []));

                foreach ($studentIds as $studentId) {
                    $journal = trim((string) $studentLearningJournals->get((string) $studentId, $studentLearningJournals->get($studentId, '')));

                    if ($journal === '') {
                        $validator->errors()->add(
                            'student_learning_journals.'.$studentId,
                            'Please fill the learning journal for each selected student.'
                        );

                        continue;
                    }

                    if (mb_strlen($journal) < 3) {
                        $validator->errors()->add(
                            'student_learning_journals.'.$studentId,
                            'Each student learning journal must be at least 3 characters.'
                        );
                    }
                }
            },
        ];
    }
}
