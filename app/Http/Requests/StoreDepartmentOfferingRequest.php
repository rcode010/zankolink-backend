<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDepartmentOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id'      => ['required', 'integer', 'exists:departments,id'],
            'zankoline_capacity' => ['required', 'integer', 'between:0,100'],
            'parallel_capacity'  => ['required', 'integer', 'between:0,100'],
            'governorate'        => ['required', 'in:Erbil,Sulaimani,Duhok,Halabja,Kirkuk'],
            'major_type'         => ['required', 'in:scientific,literary'],
            'city'               => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->integer('zankoline_capacity') + $this->integer('parallel_capacity') !== 100) {
                    $validator->errors()->add(
                        'zankoline_capacity',
                        'Zankoline and parallel capacities must add up to exactly 100.'
                    );
                }
            },
        ];
    }
}
