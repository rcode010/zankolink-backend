<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GradeStudentSubmissionRequest
 *
 * Validates the payload for a lecturer grading a student's submission
 * (PUT /api/student-submissions/{studentSubmission}/grade).
 *
 * Expected request body:
 * {
 *   "grade": 8.5,
 *   "feedback": "Good work, but check question 3 again.",
 *   "weight": 10
 * }
 *
 * NOTE: `weight` belongs to the assignment (SectionSubmission), not to
 * this individual student's submission — every student's grade for the
 * same assignment counts toward the same percentage of the final course
 * grade. Sending it here lets a lecturer set/update it the first time
 * they grade any submission for that assignment, so it's included in
 * this request even though it isn't stored on the StudentSubmission row.
 */
class GradeStudentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',

        ];
    }

    public function messages(): array
    {
        return [
            'grade.required' => 'Please provide a grade for this submission.',
            'grade.numeric' => 'The grade must be a number.',
            'grade.min' => 'The grade cannot be negative.',
            'grade.max' => 'The grade cannot exceed 100.',
            
        ];
    }
}