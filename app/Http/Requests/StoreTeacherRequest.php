<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id|unique:teachers,user_id',

            'title' => [
                'required',
                Rule::in([
                    'prof',
                    'assoc_prof',
                    'asst_prof',
                    'lecturer',
                    'dr',
                    'mr',
                    'ms',
                ]),
            ],

            'speciality' => 'required|string|max:255',
        ];
    }
}
