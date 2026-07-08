<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentAttendanceRequest extends FormRequest
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
            'attendance_session_id' => 'required|exists:course_attendance_sessions,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:Present,Absent,Excused Absence,Late',
            'note' => 'nullable|string',
        ];
    }
}
