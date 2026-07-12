<?php

namespace App\Http\Requests;

use App\Models\StudentMarks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentMarkRequest extends FormRequest
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
        $mark = $this->route('mark');
        if (! $mark instanceof StudentMarks) {
            $mark = StudentMarks::with('courseAssessment')->find($mark);
        } else {
            $mark->loadMissing('courseAssessment');
        }

        $maxMark = $mark?->courseAssessment?->max_mark;

        return [
            'mark' => [
                'required',
                'numeric',
                'min:0',
                'max:'.$maxMark,
            ],
            'feedback' => [
                'required',
                'string',
                'max:1000',
            ],
            'status' => [
                'required',
                'string',
                'in:valid,voided,excused,absent,under_review',
            ],
        ];
    }
}
