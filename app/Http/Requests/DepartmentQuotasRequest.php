<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentQuotasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'department_offering_id' => 'required|exists:department_offerings,id',
            'locality_type' => 'required|in:internal,external',
            'capacity' => 'required|integer',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
