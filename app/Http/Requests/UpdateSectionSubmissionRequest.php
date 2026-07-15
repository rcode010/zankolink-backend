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
            'files.*' => 'file|max:10240|mimes:doc,docx,pdf,ppt,pptx,jpg,jpeg,png',


            'title' => 'sometimes|string',
            'max_mark' => 'sometimes|numeric|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'due_at' => 'nullable|date',
        ];
    }
}
