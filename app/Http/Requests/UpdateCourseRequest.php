<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
            'department_id' => 'sometimes|exists:departments,id',
            'name' => 'sometimes|string|max:255',

            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('courses', 'code')
                    ->where('department_id', $this->department_id)
                    ->ignore($this->route('course')),
            ],

            'credit_hours' => 'sometimes|integer|min:1',
            'year_level' => 'sometimes|integer|min:1',
            'is_active' => 'nullable|boolean',
            'semester' => 'sometimes|string|in:spring,fall',
            'prerequisites' => ['sometimes', 'array'],
            'prerequisites.*' => ['integer', 'exists:courses,id'],
            'color' => 'sometimes|string|max:255',
        ];
    }
}
