<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUniversityRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'admin_id' => 'nullable|exists:users,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'location' => 'sometimes|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'established_year' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
