<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseAssessmentRequest extends FormRequest
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
            'academic_year_id' => 'required|exists:academic_years,id',
            'title' => 'required|string',
            'type' => 'required|string|in:quiz,assignment,final,midterm,project,activity',
            'max_mark' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'due_at' => 'nullable|date',
            'is_published' => 'required|boolean',
        ];
    }
}
