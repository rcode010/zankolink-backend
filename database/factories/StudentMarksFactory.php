<?php

namespace Database\Factories;

use App\Models\CourseAssessments;
use App\Models\Student;
use App\Models\StudentMarks;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentMarks>
 */
class StudentMarksFactory extends Factory
{
    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'valid',
            'valid',
            'valid',
            'valid',
            'voided',
            'excused',
            'absent',
            'under_review',
        ]);

        return [
            'course_assessment_id' => CourseAssessments::factory(),
            'student_id' => Student::factory(),
            'mark' => $status === 'valid'
                ? $this->faker->randomFloat(2, 0, 100)
                : null,
            'feedback' => $this->faker->optional(0.3)->sentence(),
            'graded_by' => Teacher::factory(),
            'graded_at' => now(),
            'status' => $status,
        ];
    }
}
