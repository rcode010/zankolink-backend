<?php

namespace App\Services;

/**
 * What one admission run did, in the order it should be read.
 *
 * The command renders it as a table and the queued job records it, so both
 * entry points report the same numbers without either of them knowing how the
 * run works.
 */
class AdmissionRunSummary
{
    /** @var array<string, string> */
    private array $metrics = [];

    /** @var array<string, array{seconds: float, peak_mb: float}> */
    private array $phases = [];

    /** @var list<string> */
    private array $warnings = [];

    public function add(string $metric, string|int|float|bool|null $value): self
    {
        $this->metrics[$metric] = match (true) {
            is_bool($value) => $value ? 'yes' : 'no',
            $value === null => '-',
            is_float($value) => number_format($value, 3),
            is_int($value) => number_format($value),
            default => (string) $value,
        };

        return $this;
    }

    public function addPhase(string $phase, float $seconds, float $peakMegabytes): self
    {
        $this->phases[$phase] = [
            'seconds' => $seconds,
            'peak_mb' => $peakMegabytes,
        ];

        return $this;
    }

    public function warn(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    /** @return array<string, string> */
    public function metrics(): array
    {
        return $this->metrics;
    }

    /** @return array<string, array{seconds: float, peak_mb: float}> */
    public function phases(): array
    {
        return $this->phases;
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function metric(string $name): ?string
    {
        return $this->metrics[$name] ?? null;
    }

    /**
     * Metrics first, then one row per phase with its elapsed time and the peak
     * memory reached by the end of it.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function toRows(): array
    {
        $rows = [];

        foreach ($this->metrics as $metric => $value) {
            $rows[] = [$metric, $value];
        }

        foreach ($this->phases as $phase => $timing) {
            $rows[] = [
                "time: {$phase}",
                sprintf('%.3fs, peak %.1f MB', $timing['seconds'], $timing['peak_mb']),
            ];
        }

        foreach ($this->warnings as $warning) {
            $rows[] = ['warning', $warning];
        }

        return $rows;
    }
}
