<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseAssessmentRequest extends FormRequest
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
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'title' => 'sometimes|string|max:255',
            'max_mark' => 'sometimes|numeric|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'due_at' => 'nullable|date|after:now',
        ];
    }
}
