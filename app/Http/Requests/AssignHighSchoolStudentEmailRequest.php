<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignHighSchoolStudentEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "email"=>"string|email|required",
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
