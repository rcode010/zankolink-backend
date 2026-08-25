<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "keyword" => ["required", "string", "max:255",'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
