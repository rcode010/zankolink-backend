<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'receiver_id' => ['required', 'exists:users,id'],
            'type' => [
                'required',
                Rule::in([
                    'hire_teacher',
                    'fire_teacher',
                    'create_department',
                    'close_department',
                    'open_faculty',
                    'close_faculty',
                    'remove_student',
                    'create_course',
                    'delete_course',
                ]),
            ],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],

            'payload' => ['required', 'array'],

            'file' => [
                'nullable',
                'array',
            ],

            'file.*' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:5120',
            ],

            ...$this->payloadRules($type),
        ];
    }

    private function payloadRules(?string $type): array
    {
        return match ($type) {
            'hire_teacher' => [
                'payload.name' => ['required', 'string', 'max:255'],
                'payload.email' => ['required', 'email', 'unique:users,email'],
                'payload.phone' => ['nullable', 'string', 'max:50', 'regex:/^07[0-9]{9}$/'],
                'payload.department_id' => ['required', 'exists:departments,id'],
                'payload.title' => ['required', 'string', 'max:255'],
                'payload.speciality' => ['required', 'string', 'max:255'],
            ],

            'fire_teacher' => [
                'payload.teacher_id' => ['required', 'exists:teachers,id'],
            ],

            'create_department' => [
                'payload.name' => ['required', 'string', 'max:255'],
                'payload.faculty_id' => ['required', 'exists:faculties,id'],
                'payload.admin_id' => ['nullable', 'exists:users,id'],
            ],

            'close_department' => [
                'payload.department_id' => ['required', 'exists:departments,id'],
            ],

            'open_faculty' => [
                'payload.name' => ['required', 'string', 'max:255'],
                'payload.university_id' => ['required', 'exists:universities,id'],
                'payload.admin_id' => ['nullable', 'exists:users,id'],
            ],

            'close_faculty' => [
                'payload.faculty_id' => ['required', 'exists:faculties,id'],
            ],
            'remove_student' => [
                'payload.student_id' => ['required', 'exists:students,id'],
            ],
            'create_course'=>[
                'payload.name' => ['required', 'string', 'max:255'],
                'payload.department_id' => ['required', 'exists:departments,id'],
                'payload.code'=>['required', 'string', 'max:50','unique:courses,code'],
                'payload.semester'=>['required', 'string','in:fall,spring'],
                'payload.credit_hours'=>['required', 'integer', 'min:1'],
                'payload.year_level'=>['required', 'integer', 'min:1'],
                'payload.is_active'=>['required', 'boolean'],
                'payload.prerequisites'=>['nullable', 'array'],
                'payload.prerequisites.*'=>['required', 'integer', 'exists:courses,id'],
                'payload.color'=>['required', 'string', 'max:255'],
            ],
            'delete_course'=>[
                'payload.course_id' => ['required', 'exists:courses,id'],
            ],

            default => [],
        };
    }
}
