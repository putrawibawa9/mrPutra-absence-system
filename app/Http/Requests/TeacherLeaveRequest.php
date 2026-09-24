<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TeacherLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            // Jam harus diisi berpasangan (atau dikosongkan dua-duanya = sehari penuh).
            if (($start && ! $end) || (! $start && $end)) {
                $validator->errors()->add('start_time', 'Isi jam mulai dan jam selesai bersamaan, atau kosongkan keduanya untuk libur sehari penuh.');

                return;
            }

            if ($start && $end && $end <= $start) {
                $validator->errors()->add('end_time', 'Jam selesai harus setelah jam mulai.');
            }
        });
    }
}
