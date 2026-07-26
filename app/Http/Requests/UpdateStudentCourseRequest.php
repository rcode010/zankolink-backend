<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class UpdateStudentCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:enrolled,passed,failed,withdrawn',
            'academic_year_id' => 'sometimes|nullable|exists:academic_years,id',
            'enrolled_at' => 'sometimes|nullable|date',
            'grade' => 'sometimes|nullable|numeric|min:0',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->has('grade')) {
                    return;
                }

                $course = $this->route('course');
                $student = $this->route('student');

                if (! $course instanceof Course || ! $student instanceof Student) {
                    return;
                }

                $academicYearId = $this->input('academic_year_id');

                if (! $academicYearId) {
                    $academicYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');
                }

                if (! $academicYearId) {
                    return;
                }

                $totalAssessmentMarks = DB::table('course_assessments')
                    ->where('course_id', $course->id)
                    ->where('academic_year_id', $academicYearId)
                    ->whereNull('deleted_at')
                    ->sum('max_mark');

                if ((float) $this->input('grade') > (float) $totalAssessmentMarks) {
                    $validator->errors()->add(
                        'grade',
                        "The grade cannot be greater than the total assessment marks ({$totalAssessmentMarks})."
                    );
                }
            },
        ];
    }
}