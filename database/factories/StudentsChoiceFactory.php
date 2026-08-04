<?php

namespace Database\Factories;

use App\Models\StudentsChoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentsChoiceFactory extends Factory
{
    protected $model = StudentsChoice::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
