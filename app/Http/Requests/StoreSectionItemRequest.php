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
            // Handle file upload validation rules
            'file' => 'required_without:url|prohibits:url|file|max:51200|mimes:pdf,jpg,jpeg,png,gif,mp4,mov,avi,doc,docx,ppt,pptx,xls,xlsx',
            
            // Handle external URL validation rules
            'url' => 'required_without:file|prohibits:file|url',
            
            // Required only if file is missing, meaning a link is being submitted
            'material_file_name' => 'required_without:file|string|max:255',
        ];
    }
}