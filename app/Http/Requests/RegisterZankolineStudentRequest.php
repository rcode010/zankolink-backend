<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterZankolineStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'integer',
                'unique:high_school_students,code',
            ],
            'name' => 'required|string',

            'major_type' => [
                'required',
                Rule::in(['scientific', 'literary']),
            ],

            'gender' => [
                'required',
                Rule::in(['male', 'female']),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'draft',
                    'submitted',
                    'accepted',
                    'rejected',
                ]),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'grade_average' => [
                'required',
                'numeric',
                'between:0,100',
            ],

            'grade_10' => [
                'required',
                'numeric',
                'between:0,100',
            ],

            'grade_11' => [
                'required',
                'numeric',
                'between:0,100',
            ],

            'accepted_department_offering_id' => [
                'nullable',
                'exists:department_offerings,id',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
