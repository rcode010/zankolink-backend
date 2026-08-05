<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentContactInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'regex:/^07[0-9]{9}$/',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:student_contact_infos,email',
            ],

            'id_number' => [
                'required',
                'string',
                'max:50',
                'unique:student_contact_infos,id_number',
            ],

            'governorate' => [
                'required',
                Rule::in([
                    'Erbil',
                    'Sulaimani',
                    'Duhok',
                    'Halabja',
                    'Kirkuk',
                ]),
            ],

            'home_address' => [
                'required',
                'string',
                'max:500',
            ],

            'emergency_contact_name' => [
                'required',
                'string',
                'max:255',
            ],

            'emergency_contact_phone' => [
                'required',
                'regex:/^07[0-9]{9}$/',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
  
}
