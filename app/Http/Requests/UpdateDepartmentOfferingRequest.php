<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentOfferingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'sometimes',
                'exists:academic_years,id',
            ],

            'track_type' => [
                'sometimes',
                'in:zankoline,parallel',
            ],

            'major_type' => [
                'sometimes',
                'in:scientific,literary',
            ],

            'governorate' => [
                'sometimes',
                'in:Erbil,Sulaimani,Duhok,Halabja,Kirkuk',
            ],

            'city' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'minimum_grade_zankoline' => [
                'sometimes',
                'numeric',
                'between:0,100',
            ],
            'minimum_grade_parallel' => [
                'sometimes',
                'numeric',
                'between:0,100',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
