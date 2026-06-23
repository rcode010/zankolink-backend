<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id|unique:students,user_id',
            'department_id' => 'required|exists:departments,id',
            'enrollment_type' => 'required|in:morning,parallel,evening',
            'stage' => 'required|integer',
            'student_number' => 'required|string|max:50|unique:students,student_number',
            'status' => 'required|in:active,inactive,on_leave,suspended,graduated',
        ];
    }
}
