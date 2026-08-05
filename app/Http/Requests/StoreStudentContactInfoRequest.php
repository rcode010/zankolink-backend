<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentContactInfoRequest extends FormRequest
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
        return [
            'phone' => 'required|regex:/^07[0-9]{9}$/',
            'email' => 'required|email|unique:student_contact_infos,email',
            'id_number' => 'required|string|unique:student_contact_infos,id_number',
            'governorate' => 'required|in:Erbil,Sulaimani,Duhok,Halabja,Kirkuk',
            'home_address' => 'required|string',
            'emergency_contact_name' => 'required|string',
            'emergency_contact_phone' => 'required|regex:/^07[0-9]{9}$/',
        ];
    }
}
