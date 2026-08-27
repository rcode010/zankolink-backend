<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateStudentEmailsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'students' => 'required|array|min:1',
            'students.*' => [
                'required',
                'integer',
                'exists:high_school_students,id',

                Rule::exists('high_school_students', 'id')
                    ->whereNotNull('accepted_department_offering_id'),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
