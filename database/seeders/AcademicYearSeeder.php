<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = range(2020, 2026);

        foreach ($years as $year) {
            $yearLabel = "{$year}-" . ($year + 1);
            $isActive = ($year === 2026);

            $startDate = Carbon::create($year, 9, 1);
            $endDate = Carbon::create($year + 1, 8, 31);

            AcademicYear::updateOrCreate(
                ['year' => $yearLabel],
                [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'semester'   => 'fall',
                    'is_active'  => $isActive,
                    'zankoline_submission_starts_at' => $isActive
                        ? now()->subWeek()->startOfDay()
                        : $startDate->copy()->subMonth()->startOfDay(),
                    'zankoline_submission_ends_at'   => $isActive
                        ? now()->addMonth()->endOfDay()
                        : $startDate->copy()->endOfDay(),
                ]
            );
        }
    }

    public function seedInactiveAcademicYears(): void {
        for ($i = 1; $i <= 5; $i++) {

        }
    }
}
