<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentApplicationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
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

            'draft_choices' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],

            'draft_choices.*.department_offering_id' => [
                'required',
                'exists:department_offerings,id',
            ],

            'draft_choices.*.preference_order' => [
                'required',
                'integer',
                'min:1',
                'max:50',
            ],

            'submitted_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
