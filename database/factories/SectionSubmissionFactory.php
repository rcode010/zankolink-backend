<?php

namespace Database\Factories;

use App\Models\CourseAssessments;
use App\Models\CourseSection;
use App\Models\SectionSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionSubmission>
 */
class SectionSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'course_assessment_id' => CourseAssessments::factory(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
