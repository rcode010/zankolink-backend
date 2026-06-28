<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->randomElement([
            'prof',
            'assoc_prof',
            'asst_prof',
            'lecturer',
            'dr',
            'mr',
            'ms',
        ]);

        $speciality = $this->faker->randomElement([
            'Computer Science',
            'Artificial Intelligence',
            'Networks',
            'Cyber Security',
            'Software Engineering',
        ]);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'speciality' => $speciality,
        ];
    }
}
