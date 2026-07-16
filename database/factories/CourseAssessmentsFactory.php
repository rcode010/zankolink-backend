<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Model;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class CourseAssessmentsFactory extends Factory
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
            'academic_year_id' => 1,
            'title' => $this->faker->words(3, true),
            'max_mark' => $this->faker->randomElement([
                10,
                20,
                25,
                30,
                50,
            ]),
            'weight' => 10,
            'due_at' => $this->faker->dateTimeBetween(
                'now',
                '+3 months'
            ),
            'is_published' => $this->faker->boolean(),
        ];
    }
}
