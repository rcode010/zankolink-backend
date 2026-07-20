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

            'create' => [
                'sometimes',
                'array',
            ],

            'create.*' => [
                'required',
                'array',
            ],

            'create.*.title' => [
                'required',
                'string',
                'max:255',
            ],

            'create.*.max_mark' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'create.*.weight' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'create.*.due_at' => [
                'nullable',
                'date',
                'after:now',
            ],

            'update' => [
                'sometimes',
                'array',
            ],

            'update.*' => [
                'required',
                'array',
            ],

            'update.*.id' => [
                'required',
                'integer',
                'distinct',
            ],

            'update.*.title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'update.*.max_mark' => [
                'sometimes',
                'numeric',
                'gt:0',
            ],

            'update.*.weight' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],

            'update.*.due_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now',
            ],

            'delete' => [
                'sometimes',
                'array',
            ],

            'delete.*' => [
                'integer',
                'distinct',
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

                $academicYearId = (int) $this->input(
                    'academic_year_id'
                );

                $createItems = collect(
                    $this->input('create', [])
                );

                $updateItems = collect(
                    $this->input('update', [])
                );

                $deleteIds = collect(
                    $this->input('delete', [])
                )->map(fn ($id) => (int) $id);

                if (
                    $createItems->isEmpty()
                    && $updateItems->isEmpty()
                    && $deleteIds->isEmpty()
                ) {
                    $validator->errors()->add(
                        'operations',
                        'At least one create, update, or delete operation is required.'
                    );

                    return;
                }


                $editableFields = [
                    'title',
                    'max_mark',
                    'weight',
                    'due_at',
                ];

                foreach ($updateItems as $index => $item) {
                    $hasUpdateField = collect($editableFields)
                        ->contains(
                            fn ($field) => array_key_exists(
                                $field,
                                $item
                            )
                        );

                    if (! $hasUpdateField) {
                        $validator->errors()->add(
                            "update.$index",
                            'At least one assessment field must be provided for update.'
                        );
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $updateIds = $updateItems
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();


                $overlappingIds = $updateIds->intersect(
                    $deleteIds
                );

                if ($overlappingIds->isNotEmpty()) {
                    $validator->errors()->add(
                        'operations',
                        'The same assessment cannot be updated and deleted in one request.'
                    );

                    return;
                }


                $existingAssessments = CourseAssessments::query()
                    ->where('course_id', $course->id)
                    ->where(
                        'academic_year_id',
                        $academicYearId
                    )
                    ->get([
                        'id',
                        'weight',
                    ])
                    ->keyBy('id');


                $referencedIds = $updateIds
                    ->merge($deleteIds)
                    ->unique()
                    ->values();

                $invalidIds = $referencedIds->reject(
                    fn ($id) => $existingAssessments->has($id)
                );

                if ($invalidIds->isNotEmpty()) {
                    $validator->errors()->add(
                        'operations',
                        'One or more assessments do not belong to this course and academic year.'
                    );

                    return;
                }


                $finalWeights = $existingAssessments
                    ->mapWithKeys(
                        fn ($assessment) => [
                            (int) $assessment->id =>
                                (float) $assessment->weight,
                        ]
                    );

                foreach ($deleteIds as $assessmentId) {
                    $finalWeights->forget($assessmentId);
                }

                foreach ($updateItems as $item) {
                    if (array_key_exists('weight', $item)) {
                        $finalWeights->put(
                            (int) $item['id'],
                            (float) $item['weight']
                        );
                    }
                }

                $createdWeight = $createItems->sum(
                    fn ($item) => (float) $item['weight']
                );

                $totalWeight = $finalWeights->sum()
                    + $createdWeight;

                if ($totalWeight > 100.00001) {
                    $validator->errors()->add(
                        'operations',
                        'The final total assessment weight may not exceed 100.'
                    );
                }
            },
        ];
    }
}
