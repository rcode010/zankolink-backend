<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZankolineDeadlineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'zankoline_submission_starts_at' => 'required|date|after_or_equal:today',
            'zankoline_submission_ends_at' => 'required|date|after:zankoline_submission_starts_at',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
