<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LetterVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Add route parameter to request validation data.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'letter_uuid' => $this->route('letter_uuid'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'letter_uuid' => ['required', 'uuid'],
        ];
    }
}
