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
            'attendance' => 'required|array|min:1',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:Present,Absent,Late,Excused Absence,Holiday',
            'attendance.*.note' => 'nullable|string',
        ];
    }
}
