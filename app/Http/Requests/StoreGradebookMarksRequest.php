<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class StoreGradebookMarksRequest extends FormRequest
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

            'marks' => [
                'required',
                'array',
                'min:1',
            ],
            'marks.*' => [
                'required',
                'array',
            ],

            'marks.*.assessment_id' => [
                'required',
                'integer',
            ],

            'marks.*.student_id' => [
                'required',
                'integer',
            ],

            // Nullable allows an existing mark to be cleared.
            'marks.*.mark' => [
                'present',
                'nullable',
                'numeric',
                'min:0',
            ],

            'marks.*.feedback' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'marks.*.status' => [
                'nullable',
                'string',
                'in:valid,voided,excused,absent,under_review',
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

                $academicYearId = (int) $this->input('academic_year_id');

                $marks = collect($this->input('marks', []));

                $pairs = $marks->map(
                    fn (array $mark) => ($mark['assessment_id'] ?? '')
                        .':'
                        .($mark['student_id'] ?? '')
                );

                if ($pairs->duplicates()->isNotEmpty()) {
                    $validator->errors()->add(
                        'marks',
                        'The same student and assessment combination cannot appear more than once.'
                    );
                }

                $assessmentIds = $marks
                    ->pluck('assessment_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $studentIds = $marks
                    ->pluck('student_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $assessments = DB::table('course_assessments')
                    ->where('course_id', $course->id)
                    ->where('academic_year_id', $academicYearId)
                    ->whereNull('deleted_at')
                    ->whereIn('id', $assessmentIds)
                    ->get([
                        'id',
                        'max_mark',
                    ])
                    ->keyBy('id');

                $enrolledStudentIds = DB::table('course_student')
                    ->where('course_id', $course->id)
                    ->where('academic_year_id', $academicYearId)
                    ->whereIn('student_id', $studentIds)
                    ->pluck('student_id')
                    ->map(fn ($id) => (int) $id)
                    ->flip();

                foreach ($marks as $index => $mark) {
                    $assessmentId = (int) ($mark['assessment_id'] ?? 0);
                    $studentId = (int) ($mark['student_id'] ?? 0);

                    $assessment = $assessments->get($assessmentId);

                    if (! $assessment) {
                        $validator->errors()->add(
                            "marks.$index.assessment_id",
                            'The assessment does not belong to this course and academic year.'
                        );
                    }

                    if (! $enrolledStudentIds->has($studentId)) {
                        $validator->errors()->add(
                            "marks.$index.student_id",
                            'The student is not enrolled in this course and academic year.'
                        );
                    }

                    if (
                        $assessment &&
                        array_key_exists('mark', $mark) &&
                        $mark['mark'] !== null &&
                        (float) $mark['mark'] > (float) $assessment->max_mark
                    ) {
                        $validator->errors()->add(
                            "marks.$index.mark",
                            "The mark may not be greater than {$assessment->max_mark}."
                        );
                    }
                }
            },
        ];
    }
}
