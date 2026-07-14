<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'file' => 'nullable|prohibits:url|file|max:51200|mimes:pdf,jpg,jpeg,png,gif,mp4,mov,avi,doc,docx,ppt,pptx,xls,xlsx',
            'url' => 'nullable|prohibits:file|url',
            'material_file_name' => 'nullable|string|max:255',
        ];
    }
}
