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
            // Metadata-only update — the file/link itself is not replaced here.
            // (Re-uploading a new file would go through destroy() + store() instead.)
            'material_file_name' => 'sometimes|required|string|max:255',
        ];
    }
}