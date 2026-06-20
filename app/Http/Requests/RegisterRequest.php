<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'position' => 'required|string|in:ministry_admin,university_admin,faculty_admin,department_head,teacher,student,applicant',
            'phone' => 'required|string|regex:/^07[0-9]{9}$/',
            'role_scope_id' => 'nullable|integer',
            'role_scope_type' => 'nullable|string|in:university,faculty,department',
        ];
    }
    public function messages(): array{
        return [
            'phone.regex' => 'Phone number must be a valid Iraqi number (e.g. 07701234567).',
        ];
    }
}
