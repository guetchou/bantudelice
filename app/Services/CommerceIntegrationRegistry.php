<?php

namespace App\Services;

use App\Contracts\CommerceIntegrationAdapter;
use App\ExternalIntegration;
use Illuminate\Support\Facades\Schema;

class CommerceIntegrationRegistry
{
    /** @var array<string, CommerceIntegrationAdapter> */
    protected array $adapters = [];

    public function register(CommerceIntegrationAdapter $adapter): void
    {
        $this->adapters[$adapter->name()] = $adapter;
    }

    public function has(string $name): bool
    {
        return isset($this->adapters[$name]);
    }

    public function healthReport(): array
    {
        if (empty($this->adapters)) {
            return $this->configHealthReport();
        }

        $report = [];

        foreach ($this->adapters as $name => $adapter) {
            $report[$name] = $adapter->health();
            $this->persistHealth($name, $report[$name]);
        }

        return $report;
    }

    public function publish(string $event, array $payload = []): array
    {
        $results = [];

        foreach ($this->adapters as $name => $adapter) {
            $results[$name] = $adapter->publish($event, $payload);
        }

        return $results;
    }

    public function configHealthReport(): array
    {
        $report = [];

        foreach ((array) config('commerce.integrations', []) as $name => $settings) {
            $ok = (bool) ($settings['enabled'] ?? false);
            $report[$name] = [
                'ok' => $ok,
                'driver' => $settings['driver'] ?? null,
                'source' => 'config',
                'message' => $ok ? 'configured' : 'disabled',
            ];
            $this->persistHealth($name, $report[$name]);
        }

        return $report;
    }

    protected function persistHealth(string $provider, array $health): void
    {
        if (!Schema::hasTable('external_integrations')) {
            return;
        }

        ExternalIntegration::updateOrCreate(
            ['provider' => $provider],
            [
                'status' => ($health['ok'] ?? false) ? 'healthy' : 'degraded',
                'last_healthy_at' => !empty($health['ok']) ? now() : null,
                'last_error_at' => empty($health['ok']) ? now() : null,
                'last_error_message' => $health['error'] ?? null,
                'metadata' => $health,
            ]
        );
    }
}
