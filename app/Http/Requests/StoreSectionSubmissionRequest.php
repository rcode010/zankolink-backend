<?php

namespace App\Http\Requests;

use App\Models\CourseAssessments;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSectionSubmissionRequest extends FormRequest
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
            'description' => 'nullable|string',

            'files' => 'nullable|array',
            'files.*' => 'file|max:10240|mimes:doc,docx,pdf,ppt,pptx,jpg,jpeg,png',

            'title' => 'required|string',
            'max_mark' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'due_at' => 'nullable|date|after:now',
        ];
    }

    public function after(): array
    {
        return [

            function ($validator) {

                $section = $this->route('section');

                $course = $section->course;

                $academicYearId = $this->input('academic_year_id');

                $currentTotalWeight = CourseAssessments::query()
                    ->where('course_id', $course->id)
                    ->when($academicYearId, function ($query) use ($academicYearId) {
                        $query->where('academic_year_id', $academicYearId);
                    })
                    ->sum('weight');

                $newTotalWeight = $currentTotalWeight + $this->input('weight');

                if ($newTotalWeight > 100) {
                    $validator->errors()->add(
                        'weight',
                        'Total assessment weight for this course cannot exceed 100%.'
                    );
                }

            }

        ];
    }
}
