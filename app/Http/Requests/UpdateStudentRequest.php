<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'name' => 'sometimes|string|max:255',

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($student->user_id),
            ],

            'phone' => 'sometimes|string|regex:/^07[0-9]{9}$/',

            'department_id' => 'sometimes|exists:departments,id',

            'enrollment_type' => [
                'sometimes',
                Rule::in(['morning', 'parallel', 'evening']),
            ],

            'stage' => 'sometimes|integer|between:1,6',

            'student_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('students', 'student_number')
                    ->ignore($student->id),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                    'on_leave',
                    'suspended',
                    'graduated',
                ]),
            ],
        ];
    }
}
