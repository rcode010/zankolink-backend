<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $enrollment_type = $this->faker->randomElement([
            'morning',
            'parallel',
            'evening',
        ]);

        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'enrollment_type' => $enrollment_type,
            'stage' => $this->faker->numberBetween(1, 4),
            'student_number' => 'ST'.$this->faker->unique()->numberBetween(10000, 99999),
            'status' => 'active',
        ];
    }
}
