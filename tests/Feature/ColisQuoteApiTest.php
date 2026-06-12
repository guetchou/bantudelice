<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColisQuoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_endpoint_validates_required_fields(): void
    {
        $this->postJson('/api/v1/colis/quotes', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['weight_kg', 'service_level']);
    }

    public function test_quote_endpoint_returns_expected_breakdown_for_express_cod_and_distance(): void
    {
        $this->postJson('/api/v1/colis/quotes', [
            'weight_kg' => 2,
            'service_level' => 'express',
            'cod_amount' => 10000,
            'declared_value' => 50000,
            'distance_km' => 25,
        ])
            ->assertStatus(200)
            ->assertJson([
                'price_breakdown' => [
                    'base_price' => 3000,
                    'express_surcharge' => 1500,
                    'cod_fee' => 700,
                    'insurance_fee' => 500,
                    'distance_surcharge' => 500,
                ],
                'total_price' => 6200,
            ]);
    }
}
