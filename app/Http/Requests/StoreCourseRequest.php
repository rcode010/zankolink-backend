<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
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
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('courses', 'code')
                    ->where('department_id', $this->department_id),
            ],
            'semester' => 'required|in:fall,spring',
            'credit_hours' => 'required|integer|min:1',
            'year_level' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'required|integer|exists:courses,id',
            'color' => 'required|string|max:255',
        ];
    }
}
