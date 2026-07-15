<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\CourseAssessments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncCourseAssessmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'integer',
                'exists:academic_years,id',
            ],

            'assessments' => [
                'required',
                'array',
            ],

            'assessments.*' => [
                'required',
                'array',
            ],

            // Missing or null ID means create a new assessment.
            'assessments.*.id' => [
                'sometimes',
                'nullable',
                'integer',
            ],

            'assessments.*.title' => [
                'required',
                'string',
                'max:255',
            ],

            'assessments.*.type' => [
                'required',
                'string',
                'in:quiz,assignment,final,midterm,project,activity',
            ],

            'assessments.*.max_mark' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'assessments.*.weight' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'assessments.*.due_at' => [
                'present',
                'nullable',
                'date',
            ],

            'assessments.*.is_published' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $course = $this->route('course');

                if (! $course instanceof Course) {
                    $course = Course::find($course);
                }

                if (! $course) {
                    return;
                }

                $items = collect($this->input('assessments', []));

                $totalWeight = $items->sum(
                    fn (array $item) => (float) $item['weight']
                );

                if ($totalWeight > 100) {
                    $validator->errors()->add(
                        'assessments',
                        'The total assessment weight may not exceed 100.'
                    );
                }

                $assessmentIds = $items
                    ->pluck('id')
                    ->filter(fn ($id) => $id !== null)
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($assessmentIds->duplicates()->isNotEmpty()) {
                    $validator->errors()->add(
                        'assessments',
                        'The same assessment cannot appear more than once.'
                    );

                    return;
                }

                if ($assessmentIds->isEmpty()) {
                    return;
                }

                $validAssessmentCount = CourseAssessments::query()
                    ->where('course_id', $course->id)
                    ->where(
                        'academic_year_id',
                        (int) $this->input('academic_year_id')
                    )
                    ->whereIn('id', $assessmentIds)
                    ->count();

                if ($validAssessmentCount !== $assessmentIds->count()) {
                    $validator->errors()->add(
                        'assessments',
                        'One or more assessments do not belong to this course and academic year.'
                    );
                }
            },
        ];
    }
}
