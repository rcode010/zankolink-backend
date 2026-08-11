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
            'zankoline_capacity' => 'required|integer|between:0,100',
            'parallel_capacity' => 'required|integer|between:0,100',
            'major_type' => 'required|in:scientific,literary',
            'governorate' => 'required|in:Erbil,Duhok,Sulaimani,Halabja,Kirkuk',
            'city' => 'required|string',
            'minimum_grade_zankoline' => 'sometimes|integer|between:1,100',
            'minimum_grade_parallel' => 'sometimes|integer|between:1,100',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $zankoline = $this->integer('zankoline_capacity');
                $parallel = $this->integer('parallel_capacity');

                if ($zankoline + $parallel !== 100) {
                    $validator->errors()->add(
                        'zankoline_capacity',
                        'Zankoline and parallel capacities must add up to exactly 100.'
                    );
                }
            },
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
