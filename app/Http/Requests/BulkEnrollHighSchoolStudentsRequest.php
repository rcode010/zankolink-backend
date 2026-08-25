<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkEnrollHighSchoolStudentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'students' => 'required|array|min:1',

            'students.*.email' => 'required|email|max:255|unique:users,email',
            'students.*.phone' => 'required|string',
            'students.*.student_id' => 'required|integer|exists:high_school_students,id',
            'students.*.department_id' => [
                'required',
                'integer',
                Rule::exists('department_offerings', 'department_id'),
            ],
            'students.*.enrollment_type' => [
                'required',
                Rule::in(['morning', 'parallel', 'evening']),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
