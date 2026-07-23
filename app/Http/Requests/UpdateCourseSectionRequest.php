<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => 'sometimes|nullable|exists:teachers,id',
            'course_id' => 'sometimes|exists:courses,id',
            'title' => 'sometimes|string|max:255',
        ];
    }
}
