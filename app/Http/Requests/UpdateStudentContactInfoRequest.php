<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentContactInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contactInfo = $this->user()->contacts;

        return [
            'phone' => [
                'required',
                'regex:/^07[0-9]{9}$/',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('student_contact_infos', 'email')
                    ->ignore($contactInfo?->id),
            ],

            'id_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_contact_infos', 'id_number')
                    ->ignore($contactInfo?->id),
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
}
