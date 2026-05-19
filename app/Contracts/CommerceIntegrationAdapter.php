<?php

namespace App\Contracts;

interface CommerceIntegrationAdapter
{
    public function name(): string;

    public function health(): array;

    public function publish(string $event, array $payload = []): array;
}
