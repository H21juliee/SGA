<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('grades.edit');
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'lapse_id' => ['required', 'integer', 'exists:lapses,id'],
            'score' => ['required', 'numeric', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.min' => 'La nota mínima es 1.',
            'score.max' => 'La nota máxima es 20.',
            'score.required' => 'La nota es obligatoria.',
        ];
    }
}
