<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseAttendanceSessionsRequest extends FormRequest
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
            'course_id' => 'sometimes|exists:courses,id',
            'session_date' => 'sometimes|date|after:now',
            'start_at' => 'sometimes|date|after:now',
            'end_at' => 'sometimes|date|after:start_at',
            'title' => 'nullable|string',
        ];
    }
}
