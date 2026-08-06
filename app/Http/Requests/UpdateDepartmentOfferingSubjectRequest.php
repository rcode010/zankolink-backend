<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentOfferingSubjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'credit' => [
                'sometimes',
                'integer',
                'between:1,10',
            ],

            'minimum_grade' => [
                'sometimes',
                'integer',
                'between:1,100',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
