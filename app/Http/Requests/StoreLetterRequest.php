<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLetterRequest extends FormRequest
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
    public function rules()
    {
        return [
            'title' => 'required|string|min:5|max:255',
            'body' => 'required|string|min:10',
            'type' => 'required|in:internal,directive,request,decision,appeal',

            'receiver_id' => 'required|integer|exists:users,id', // Your controller will handle dynamic validation

            'original_sender_id' => 'required|integer',

            'status' => 'required|in:pending,approved,rejected',

            'academic_year' => 'required|string|regex:/^\d{4}-\d{4}$/',

        ];
    }
}
