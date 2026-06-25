<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ProgressiveDispatchPlan
{
    public function radiusSteps(): array
    {
        $steps = array_values(array_filter(
            (array) config('food.dispatch.radius_steps_km', [5, 10, 20, 40]),
            static fn ($value) => is_numeric($value) && (float) $value > 0
        ));

        $steps = array_map('floatval', $steps);
        sort($steps, SORT_NUMERIC);

        return $steps ?: [5.0, 10.0, 20.0, 40.0];
    }

    public function maxRounds(): int
    {
        return count($this->radiusSteps());
    }

    public function radiusForRound(int $round): float
    {
        $steps = $this->radiusSteps();
        $index = max(0, min(count($steps) - 1, $round - 1));

        return $steps[$index];
    }

    public function batchSize(): int
    {
        return max(1, (int) config('food.dispatch.batch_size', 3));
    }

    public function candidatePoolSize(): int
    {
        return max($this->batchSize(), (int) config('food.dispatch.candidate_pool_size', 100));
    }

    public function offerWindowSeconds(): int
    {
        return max(15, (int) config('food.dispatch.offer_window_seconds', 45));
    }

    public function noCandidateDelaySeconds(): int
    {
        return max(10, (int) config('food.dispatch.no_candidate_delay_seconds', 60));
    }

    public function selectCandidates(Collection $rankedCandidates, int $round): Collection
    {
        $radius = $this->radiusForRound($round);

        return $rankedCandidates
            ->filter(function (array $candidate) use ($radius): bool {
                $distance = $candidate['distance_km'] ?? null;

                return $distance !== null && (float) $distance <= $radius;
            })
            ->take($this->batchSize())
            ->values();
    }
}
