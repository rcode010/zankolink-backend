<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => 'required|string|regex:/^07[0-9]{9}$/',
            'role' => 'required|string|exists:roles,name',
            'scope_id' => 'nullable|integer',
            'scope_type' => 'required|string|in:MINISTRY,UNIVERSITY,FACULTY,DEPARTMENT',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a valid Iraqi number (e.g. 07701234567).',
        ];
    }
}
