<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<University>
 */
class UniversityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a start date for the administrator's tenure (e.g., within the last 3 years)
        $tenureStartDate = $this->faker->dateTimeBetween('-3 years', '-1 years')->format('Y-m-d');

        // Generate an end date for the tenure (e.g., set to expire 1 to 3 years in the future)
        $tenureEndDate = $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d');

        return [
            'name' => $this->faker->city().'University',
            'admin_id' => null,
            'academic_year' => '2026-2027',
            'location' => $this->faker->city(),
            'start_date' => $tenureStartDate,
            'end_date' => $this->faker->optional(0.8, null)->passthrough($tenureEndDate),
            'established_year' => $this->faker->date('Y-m-d', '2010-01-01'),
            'is_active' => true,
        ];
    }
}
