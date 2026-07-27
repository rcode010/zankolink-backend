<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|prohibits:url|prohibited_if:remove_material,true|max:9216|mimes:pdf,jpg,jpeg,png,doc,docx,ppt,pptx,xls,xlsx,txt',
            'url' => 'nullable|url|prohibits:file|prohibited_if:remove_material,true',
            'material_file_name' => 'nullable|string|max:255',
            'remove_material' => 'sometimes|boolean',
        ];
    }
}
