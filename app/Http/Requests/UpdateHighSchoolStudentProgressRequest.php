<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHighSchoolStudentProgressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'step_path' => 'required|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
