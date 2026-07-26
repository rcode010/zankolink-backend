<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionSubmissionRequest extends FormRequest
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
        return [
            'description' => 'nullable|string',

            'files' => 'nullable|array',
            'files.*' => 'file|max:9216|mimes:doc,docx,pdf,ppt,pptx,jpg,jpeg,png,txt',

            'title' => 'sometimes|string',
            'max_mark' => 'sometimes|numeric|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'due_at' => 'nullable|date|after:now',
        ];
    }
}
