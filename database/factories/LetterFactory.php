<?php

namespace Database\Factories;

use App\Models\Letter;
use App\Models\User;
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
            'appeal'
        ]);

        $status = $this->faker->randomElement([
            'pending',
            'approved',
            'rejected'
        ]);

        return [
            'letter_number' => 'LTR' . $this->faker->unique()->numerify('######'),
            'original_sender_id' => null,
            'sender_id' => null,
            'receiver_id' => null,
            'type' => $type,
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(3, true),
            'is_read' => $this->faker->boolean(),
            'academic_year' => '2025-2026',
            'is_archived' => false,
            'status' => $status
        ];
    }
}
