<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAcademicYearByIdRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
