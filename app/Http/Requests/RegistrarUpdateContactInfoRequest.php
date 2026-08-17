<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarUpdateContactInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'sometimes',
                'email',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'string',
                'max:20',
            ],

            'id_number' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'governorate' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'home_address' => [
                'sometimes',
                'string',
                'max:500',
            ],

            'emergency_contact_name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'emergency_contact_phone' => [
                'sometimes',
                'string',
                'max:20',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
