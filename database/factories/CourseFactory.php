<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Introduction to IT',
            'Programming Fundamentals',
            'Data Structures',
            'Database Systems',
            'Software Architecture',
            'Web Development',
            'Mobile Application Development',
            'Artificial Intelligence',
            'Computer Networks',
            'Operating Systems',
            'Cyber Security',
            'Software Quality Assurance',
        ];

        $yearLevel = $this->faker->numberBetween(1, 4);

        $prefix = $this->faker->randomElement(['KOU', 'UOS', 'SUE']);
        $code = $prefix.$this->faker->unique()->numerify('#####');

        return [
            'name' => $this->faker->randomElement($subjects),
            'department_id' => Department::factory(),
            'code' => $code,
            'semester'=> $this->faker->randomElement(['fall','spring']),
            'year_level' => $yearLevel,
            'credit_hours' => $this->faker->randomElement([2, 3, 4]),
            'is_active' => $this->faker->randomElement([true, false]),
            'color' => $this->faker->unique()->safeHexColor(),
        ];
    }
}
