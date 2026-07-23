<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for the section.',
            'title.max' => 'The section title must not exceed 255 characters.',
        ];
    }
}
