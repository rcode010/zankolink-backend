<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $academicYearId = $this->input('id');
        $isCreating = ! $academicYearId;

        return [
            'id' => [
                'sometimes',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],

            'year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('academic_years', 'year')
                    ->ignore($academicYearId),
            ],

            'start_date' => [
                Rule::requiredIf($isCreating),
                'required_with:end_date',
                'date',
            ],

            'end_date' => [
                Rule::requiredIf($isCreating),
                'required_with:start_date',
                'date',
                'after:start_date',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
