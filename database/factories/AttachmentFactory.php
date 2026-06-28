<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Letter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
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
            'file_name' => $this->faker->word().'.pdf',
            'file_type' => 'pdf',
            'file_size' => $this->faker->numberBetween(10000, 5000000),
            'file_url' => '/storage/files/'.$this->faker->uuid().'.pdf',
        ];
    }
}
