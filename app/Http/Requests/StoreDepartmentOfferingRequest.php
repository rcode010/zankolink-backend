<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentOfferingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'department_id' => 'required|exists:departments,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'track_type' => 'required|in:zankoline,parallel',
            'major_type' => 'required|in:scientific,literary',
            'governorate' => 'required|in:Erbil,Duhok,Sulaimani,Halabja,Kirkuk',
            'city' => 'required|string',
            'minimum_grade_zankoline' => 'required|integer|between:1,100',
            'minimum_grade_parallel' => 'required|integer|between:1,100',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
