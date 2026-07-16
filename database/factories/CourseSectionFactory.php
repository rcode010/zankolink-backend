<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseSection>
 */
class CourseSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'teacher_id' => Teacher::factory(),
            'title' => 'Section '.$this->faker->randomLetter(),
        ];
    }
}
