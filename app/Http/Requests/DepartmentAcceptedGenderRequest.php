<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentAcceptedGenderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'accepted_gender' => 'required|in:male,female,both',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
