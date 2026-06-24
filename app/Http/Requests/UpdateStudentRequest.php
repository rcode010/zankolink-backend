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
        return [
            'user_id' => [
                'sometimes', 'exists:users,id',
                Rule::unique('students', 'user_id')->ignore($this->student),
            ],

            'department_id' => 'sometimes|exists:departments,id',
            'enrollment_type' => 'sometimes|in:morning,parallel,evening',
            'stage' => 'sometimes|integer',

            'student_number' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('students', 'student_number')->ignore($this->student),
            ],

            'status' => 'sometimes|in:active,inactive,on_leave,suspended,graduated',
        ];
    }
}
