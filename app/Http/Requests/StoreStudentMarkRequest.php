<?php

namespace App\Http\Requests;

use App\Models\CourseAssessments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class StoreStudentMarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assessment = $this->route('assessment');

        if (! $assessment instanceof CourseAssessments) {
            $assessment = CourseAssessments::find($assessment);
        }

        $maxMark = $assessment?->max_mark ?? 0;

        return [
            'marks' => ['required', 'array', 'min:1'],

            'marks.*.student_id' => [
                'required',
                'integer',
                'distinct',
                'exists:students,id',
            ],

            'marks.*.mark' => [
                'required',
                'numeric',
                'min:0',
                'max:'.$maxMark,
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
                $assessment = $this->route('assessment');

                if (! $assessment instanceof CourseAssessments) {
                    $assessment = CourseAssessments::find($assessment);
                }

                if (! $assessment) {
                    return;
                }

                $studentIds = collect($this->input('marks', []))
                    ->pluck('student_id')
                    ->filter()
                    ->unique()
                    ->values();

                $enrolledStudentIds = DB::table('course_student')
                    ->where('course_id', $assessment->course_id)
                    ->where('academic_year_id', $assessment->academic_year_id)
                    ->whereIn('student_id', $studentIds)
                    ->pluck('student_id');

                $notEnrolled = $studentIds->diff($enrolledStudentIds);

                if ($notEnrolled->isNotEmpty()) {
                    $validator->errors()->add(
                        'marks',
                        'Some students are not enrolled in this course.'
                    );
                }
            },
        ];
    }
}
