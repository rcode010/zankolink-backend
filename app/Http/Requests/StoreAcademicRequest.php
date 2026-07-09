<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'subject' => 'required|string',
            'description' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'files' => ['sometimes', 'array'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
