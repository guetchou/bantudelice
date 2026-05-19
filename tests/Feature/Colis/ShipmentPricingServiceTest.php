<?php

namespace Tests\Feature\Colis;

use App\Domain\Colis\Services\ShipmentPricingService;
use Tests\TestCase;

class ShipmentPricingServiceTest extends TestCase
{
    private ShipmentPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new ShipmentPricingService();
    }

    public function test_base_pricing_is_correct()
    {
        // 1kg = 1500
        $result = $this->pricingService->calculate(['weight_kg' => 1]);
        $this->assertEquals(1500, $result['total_price']);

        // 5kg = 3000
        $result = $this->pricingService->calculate(['weight_kg' => 5]);
        $this->assertEquals(3000, $result['total_price']);

        // 25kg = 8000 + (5 * 300) = 9500
        $result = $this->pricingService->calculate(['weight_kg' => 25]);
        $this->assertEquals(9500, $result['total_price']);
    }

    public function test_express_surcharge_is_applied()
    {
        // 1kg Express = 1500 + 750 = 2250
        $result = $this->pricingService->calculate([
            'weight_kg' => 1,
            'service_level' => 'express'
        ]);
        $this->assertEquals(2250, $result['total_price']);
        $this->assertArrayHasKey('express_surcharge', $result['price_breakdown']);
    }

    public function test_cod_fee_is_applied()
    {
        // 1kg + COD 10000 = 1500 + 500 + (10000 * 0.02) = 1500 + 500 + 200 = 2200
        $result = $this->pricingService->calculate([
            'weight_kg' => 1,
            'cod_amount' => 10000
        ]);
        $this->assertEquals(2200, $result['total_price']);
        $this->assertEquals(700, $result['price_breakdown']['cod_fee']);
    }

    public function test_insurance_fee_is_applied()
    {
        // 1kg + 50000 value = 1500 + (50000 * 0.01) = 1500 + 500 = 2000
        $result = $this->pricingService->calculate([
            'weight_kg' => 1,
            'declared_value' => 50000
        ]);
        $this->assertEquals(2000, $result['total_price']);
        $this->assertEquals(500, $result['price_breakdown']['insurance_fee']);
    }
}

