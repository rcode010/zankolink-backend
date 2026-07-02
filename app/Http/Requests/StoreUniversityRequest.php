<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniversityRequest extends FormRequest
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
            // University fields
            'name' => ['required', 'string', 'max:255'],
            'admin_id' => ['nullable', 'exists:users,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'established_year' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],

            // Faculties array
            'faculties' => ['required', 'array', 'min:1'],

            // Required fields inside each faculty
            'faculties.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'faculties.*.location' => ['nullable', 'string', 'max:255'],
            'faculties.*.is_active' => ['nullable', 'boolean'],

            // Departments inside each faculty
            'faculties.*.departments' => ['required', 'array', 'min:1'],

            // Required fields inside each department
            'faculties.*.departments.*.name' => ['required', 'string', 'max:255'],
            'faculties.*.departments.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
