<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        // Capture the route parameter dynamically (works with both {id} or {department})
        $departmentParam = $this->route('department') ?? $this->route('id');

        // Extract the integer ID if the parameter is a bound Model instance
        $departmentId = is_object($departmentParam) ? $departmentParam->id : $departmentParam;

        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                // Ignore the current department ID to prevent unique validation failure during updates
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'faculty_id' => 'sometimes|required|exists:faculties,id',
        ];
    }
}
