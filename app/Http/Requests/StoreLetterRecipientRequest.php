<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLetterRecipientRequest extends FormRequest
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
        $type = $this->input('type');

        return [
            'recipient_ids' => [
                'required',
                'array',
                'min:2',
            ],

            'recipient_ids.*' => [
                'integer',
                'exists:users,id',
                'distinct',
            ],

            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],

            'file' => [
                'nullable',
                'array',
            ],

            'file.*' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }
}
