<?php

namespace Tests\Unit\Services;

use App\Services\ProgressiveDispatchPlan;
use Tests\TestCase;

class ProgressiveDispatchPlanTest extends TestCase
{
    public function test_it_expands_radius_by_round_and_limits_the_batch(): void
    {
        config([
            'food.dispatch.radius_steps_km' => [5, 10, 20, 40],
            'food.dispatch.batch_size' => 2,
        ]);

        $plan = new ProgressiveDispatchPlan();
        $candidates = collect([
            ['driver' => (object) ['id' => 1], 'score' => 95, 'distance_km' => 4.5],
            ['driver' => (object) ['id' => 2], 'score' => 90, 'distance_km' => 8.0],
            ['driver' => (object) ['id' => 3], 'score' => 85, 'distance_km' => 15.0],
            ['driver' => (object) ['id' => 4], 'score' => 80, 'distance_km' => null],
        ]);

        $this->assertSame(4, $plan->maxRounds());
        $this->assertSame(5.0, $plan->radiusForRound(1));
        $this->assertSame(10.0, $plan->radiusForRound(2));
        $this->assertSame(40.0, $plan->radiusForRound(4));

        $this->assertSame([1], $plan->selectCandidates($candidates, 1)->pluck('driver.id')->all());
        $this->assertSame([1, 2], $plan->selectCandidates($candidates, 2)->pluck('driver.id')->all());
        $this->assertSame([1, 2], $plan->selectCandidates($candidates, 3)->pluck('driver.id')->all());
    }

    public function test_it_sanitizes_invalid_configuration(): void
    {
        config([
            'food.dispatch.radius_steps_km' => [0, -2, 'bad'],
            'food.dispatch.batch_size' => 0,
            'food.dispatch.offer_window_seconds' => 2,
            'food.dispatch.no_candidate_delay_seconds' => 1,
        ]);

        $plan = new ProgressiveDispatchPlan();

        $this->assertSame([5.0, 10.0, 20.0, 40.0], $plan->radiusSteps());
        $this->assertSame(1, $plan->batchSize());
        $this->assertSame(15, $plan->offerWindowSeconds());
        $this->assertSame(10, $plan->noCandidateDelaySeconds());
    }
}
