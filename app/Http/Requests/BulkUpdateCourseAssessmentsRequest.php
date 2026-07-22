<?php

namespace App\Http\Requests;

use App\Models\CourseAssessments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkUpdateCourseAssessmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessments' => ['required', 'array', 'min:1'],

            'assessments.*.id' => [
                'required',
                'integer',
                'distinct',
                'exists:course_assessments,id',
            ],

            'assessments.*.academic_year_id' => [
                'sometimes',
                'integer',
                'exists:academic_years,id',
            ],

            'assessments.*.title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'assessments.*.max_mark' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'assessments.*.weight' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'assessments.*.due_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now'
            ],

            'assessments.*.is_published' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('assessments', []) as $index => $assessment) {
                    $fields = collect($assessment)->except('id');

                    if ($fields->isEmpty()) {
                        $validator->errors()->add(
                            "assessments.$index",
                            'At least one field must be provided for update.'
                        );
                    }
                }

                $course = $this->route('course');

                $assessmentIds = collect($this->input('assessments', []))
                    ->pluck('id')
                    ->filter()
                    ->values();

                if ($assessmentIds->isEmpty()) {
                    return;
                }

                $existingAssessments = CourseAssessments::query()
                    ->where('course_id', $course->id)
                    ->whereIn('id', $assessmentIds)
                    ->get()
                    ->keyBy('id');

                $updatedTotalWeight = CourseAssessments::query()
                    ->where('course_id', $course->id)
                    ->whereNotIn('id', $assessmentIds)
                    ->sum('weight');

                foreach ($this->input('assessments', []) as $assessment) {
                    $existingAssessment = $existingAssessments->get($assessment['id']);

                    if (! $existingAssessment) {
                        continue;
                    }

                    $updatedTotalWeight += $assessment['weight'] ?? $existingAssessment->weight;
                }

                if ($updatedTotalWeight > 100) {
                    $validator->errors()->add(
                        'assessments',
                        'Total assessment weight for this course cannot exceed 100%.'
                    );
                }
            },
        ];
    }
}
