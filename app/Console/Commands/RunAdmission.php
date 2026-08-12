<?php

namespace App\Console\Commands;

use App\Services\AdmissionRunService;
use Illuminate\Console\Command;

class RunAdmission extends Command
{
    protected $signature = 'admission:run
        {--limit= : Only the top N students by grade average}
        {--dry-run : Allocate and report without writing anything}
        {--year= : Academic year id, defaults to the active one}';

    protected $description = 'Run the zankoline admission allocation';

    public function handle(AdmissionRunService $service): int
    {
        $summary = $service->run(
            academicYearId: $this->option('year') ? (int) $this->option('year') : $service->activeYearId(),
            limit: $this->option('limit') ? (int) $this->option('limit') : null,
            dryRun: (bool) $this->option('dry-run'),
            onProgress: fn (string $message, ?int $percent) => $this->line($message),
        );

        $this->table(['Metric', 'Value'], $summary->toRows());

        return self::SUCCESS;
    }
}
