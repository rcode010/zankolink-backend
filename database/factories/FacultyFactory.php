<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colleges = [
            'College of Engineering',
            'College of Science',
            'College of Medicine',
            'College of Dentistry',
            'College of Pharmacy',
            'College of Nursing',
            'College of Law and Politics',
            'College of Administration and Economics',
            'College of Humanities',
            'College of Basic Education',
            'College of Agricultural Engineering',
            'College of Fine Arts',
            'College of Languages',
            'College of Physical Education'
        ];
        return [
            'name' => $this->faker->randomElement($colleges),
            'university_id' => University::factory(),
            'admin_id'=>null,
            'is_active' => true
        ];
    }
}
