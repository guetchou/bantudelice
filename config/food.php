<?php

return [
    'dispatch' => [
        'radius_steps_km' => array_values(array_filter(array_map(
            static fn ($value) => (float) trim($value),
            explode(',', (string) env('FOOD_DISPATCH_RADIUS_STEPS_KM', '5,10,20,40'))
        ), static fn ($value) => $value > 0)),
        'batch_size' => (int) env('FOOD_DISPATCH_BATCH_SIZE', 3),
        'candidate_pool_size' => (int) env('FOOD_DISPATCH_CANDIDATE_POOL_SIZE', 100),
        'offer_window_seconds' => (int) env('FOOD_DISPATCH_OFFER_WINDOW_SECONDS', 45),
        'no_candidate_delay_seconds' => (int) env('FOOD_DISPATCH_NO_CANDIDATE_DELAY_SECONDS', 60),
    ],
];
