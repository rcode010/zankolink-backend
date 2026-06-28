<?php

namespace Database\Factories;

use App\Models\Letter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Letter>
 */
class LetterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            'internal',
            'directive',
            'request',
            'decision',
            'appeal',
        ]);

        $status = $this->faker->randomElement([
            'pending',
            'approved',
            'rejected',
        ]);

        return [
            'original_sender_id' => null,
            'sender_id' => null,
            'receiver_id' => null,
            'type' => $type,
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(3, true),
            'academic_year_id' => null,
            'verification_hash' => $this->faker->sha256(),
            'status' => $status,
        ];
    }
}
