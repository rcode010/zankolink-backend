<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentOfferingSubjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],

            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('department_offering_subjects')
                    ->where(fn ($query) => $query->where(
                        'department_id',
                        $this->department_id
                    )),
            ],

            'credit' => [
                'required',
                'integer',
                'min:1',
            ],

            'minimum_grade' => [
                'required',
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
