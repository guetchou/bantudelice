<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointsTest extends TestCase
{
    public function test_liveness_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health/live');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'service', 'timestamp']);
    }

    public function test_readiness_endpoint_returns_database_status(): void
    {
        $response = $this->getJson('/health/ready');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('ready', true)
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.redis', null)
            ->assertJsonStructure(['status', 'ready', 'checks', 'timestamp']);
    }
}
