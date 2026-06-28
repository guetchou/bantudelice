<?php

namespace App\Domain\GePay\Services;

use App\Domain\GePay\Models\GePayClient;
use RuntimeException;

final class GePayInternalClientResolver
{
    public function resolve(): GePayClient
    {
        $uuid = config('gepay.internal_client_uuid');

        if (! $uuid) {
            throw new RuntimeException(
                'GEPAY_INTERNAL_CLIENT_UUID is not configured. '
                . 'Run: php artisan gepay:client-create and set the UUID in .env'
            );
        }

        $client = GePayClient::where('uuid', $uuid)
            ->where('is_active', true)
            ->first();

        if (! $client) {
            throw new RuntimeException(
                'GePay internal client not found or inactive for UUID: ' . $uuid
                . '. Run: php artisan gepay:client-list'
            );
        }

        return $client;
    }
}
