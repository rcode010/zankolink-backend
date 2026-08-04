<?php

namespace Database\Factories;

use App\Models\DepartmentOfferings;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DepartmentOfferingsFactory extends Factory
{
    protected $model = DepartmentOfferings::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
