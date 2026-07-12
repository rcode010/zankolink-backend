<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSectionSubmissionRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date|after:|date_format:Y-m-d',
            'weight' => 'nullable|numeric|min:0|max:100',

            'files' => 'nullable|array',
            'files.*' => 'file|max:10240|mimes:doc,docx,pdf,ppt,pptx,jpg,jpeg,png',
        ];
    }
}
