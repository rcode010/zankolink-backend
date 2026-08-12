<?php

namespace App\Jobs;

use App\Services\AdmissionRunService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunAdmissionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $academicYearId)
    {
        $this->onQueue('admission');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("admission:{$this->academicYearId}"))
                ->dontRelease()
                ->expireAfter(3600),
        ];
    }

    /**
     * The same service call the Artisan command makes, with the cache as the
     * output sink instead of the console. Nothing about the run lives here, so
     * a queued run and a local run cannot drift apart.
     */
    public function handle(AdmissionRunService $service): void
    {
        $this->progress('running', 'Starting', 0);

        $summary = $service->run(
            academicYearId: $this->academicYearId,
            onProgress: fn (string $message, ?int $percent) => $this->progress('running', $message, $percent),
        );

        $placed = $summary->metric('students placed') ?? '0';
        $unplaced = $summary->metric('students unplaced') ?? '0';

        $this->progress(
            'completed',
            "Placed {$placed} students, {$unplaced} unplaced.",
            100,
            [
                'summary' => $summary->toRows(),
                'warnings' => $summary->warnings(),
            ]
        );
    }

    public function failed(?Throwable $e): void
    {
        $this->progress('failed', $e?->getMessage() ?? 'Unknown error', null);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function progress(string $status, string $message, ?int $percent, array $extra = []): void
    {
        Cache::put(self::key($this->academicYearId), [
            'status' => $status,
            'message' => $message,
            'percent' => $percent,
            'updated_at' => now()->toIso8601String(),
            ...$extra,
        ], now()->addDay());
    }

    public static function key(int $academicYearId): string
    {
        return "admission:run:{$academicYearId}";
    }
}
