<?php

namespace Database\Factories;

use App\Models\Letter;
use App\Models\LetterSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LetterSignature>
 */
class LetterSignatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'letter_id' => Letter::factory(),
            'user_id' => User::factory(),
            'comment' => $this->faker->optional()->sentence(),
            'verification_hash' => $this->faker->sha256()
        ];
    }
}
