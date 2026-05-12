<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendance.manage');
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(array_column(AttendanceStatus::cases(), 'value'))],
            'lapse_id' => ['required', 'integer', 'exists:lapses,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
