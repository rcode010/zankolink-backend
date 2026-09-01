<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateHighSchoolStudentProgressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'step_path' => [
                'required',
                new Enum(ApplicationStep::class),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
