<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    /**
     * Authorization is handled by route middleware / scope checks
     * inside the controller — this request only validates input shape.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope_type' => ['required', 'string', 'in:MINISTRY,UNIVERSITY,FACULTY,DEPARTMENT'],
            'scope_id' => ['nullable', 'integer'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        ];
    }
}