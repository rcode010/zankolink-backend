<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\SectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SectionItem>
 */
class SectionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = $this->faker->randomElement([
            [
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
            ],
            [
                'extension' => 'docx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            [
                'extension' => 'pptx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ],
            [
                'extension' => 'xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            [
                'extension' => 'png',
                'mime_type' => 'image/png',
            ],
            [
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
            ],
        ]);

        $fileName = Str::slug(
            $this->faker->words(3, true)
        ).'.'.$file['extension'];

        return [
            'section_id' => CourseSection::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'material_file_name' => $fileName,
            'material_file_type' => $file['mime_type'],
            'material_file_url' => 'materials/'.$fileName,
        ];
    }
}
