<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollHighSchoolStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone'=>[
              'required',
              'string',
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('department_offerings', 'department_id')
                    ->where(function ($query) {
                        $query->where('id', $this->route('highSchoolStudent')
                            ->accepted_department_offering_id);
                    }),
            ],

            'enrollment_type' => [
                'required',
                Rule::in(['morning', 'parallel', 'evening']),
            ],

        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
