<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentChoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'choices' => ['required', 'array', 'min:1', 'max:50'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'choices.*.department_offering_id' => [
                'required',
                'distinct',
                Rule::exists('department_offerings', 'id')->where(function ($query) use ($student, $activeYearId) {
                    $query->where('academic_year_id', $activeYearId);

                    if ($student->major_type === 'literary') {
                        $query->where('major_type', 'literary');
                    }
                }),
            ],
            'choices.*.preference_order' => [
                'required',
                'integer',
                'min:1',
                'distinct',

            ],
            'choices.*.score' => [
                'required',
                'numeric',
                'decimal:0,3',
                'between:0,100',
            ],
            'choices.*.is_local' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
