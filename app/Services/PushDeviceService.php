<?php

namespace App\Services;

use App\Driver;
use App\DriverToken;
use App\User;
use App\UserToken;
use Carbon\Carbon;

class PushDeviceService
{
    public function registerForUser(User $user, string $deviceToken, array $attributes = []): UserToken
    {
        return $this->upsertToken(UserToken::class, 'user_id', (int) $user->id, $deviceToken, $attributes);
    }

    public function registerForDriver(Driver $driver, string $deviceToken, array $attributes = []): DriverToken
    {
        return $this->upsertToken(DriverToken::class, 'driver_id', (int) $driver->id, $deviceToken, $attributes);
    }

    public function deactivateForUser(User $user, string $deviceToken): bool
    {
        return (bool) UserToken::query()
            ->where('user_id', $user->id)
            ->where('device_tokens', $deviceToken)
            ->update([
                'active' => false,
                'last_seen_at' => Carbon::now(),
            ]);
    }

    public function deactivateForDriver(Driver $driver, string $deviceToken): bool
    {
        return (bool) DriverToken::query()
            ->where('driver_id', $driver->id)
            ->where('device_tokens', $deviceToken)
            ->update([
                'active' => false,
                'last_seen_at' => Carbon::now(),
            ]);
    }

    protected function upsertToken(string $modelClass, string $ownerColumn, int $ownerId, string $deviceToken, array $attributes)
    {
        $metadata = $this->normalizeMetadata($attributes);

        $payload = [
            $ownerColumn => $ownerId,
            'device_tokens' => $deviceToken,
            'platform' => $attributes['platform'] ?? null,
            'locale' => $attributes['locale'] ?? app()->getLocale(),
            'site_key' => $attributes['site_key'] ?? app(SiteContextService::class)->currentSiteKey(),
            'active' => true,
            'last_seen_at' => Carbon::now(),
            'metadata' => !empty($metadata) ? $metadata : null,
        ];

        $record = $modelClass::query()->updateOrCreate(
            [
                $ownerColumn => $ownerId,
                'device_tokens' => $deviceToken,
            ],
            $payload
        );

        return $record;
    }

    protected function normalizeMetadata(array $attributes): array
    {
        $metadata = $attributes['metadata'] ?? [];
        $modules = collect($attributes['modules'] ?? data_get($metadata, 'subscriptions.modules', []))
            ->filter(fn ($module) => in_array($module, ['food', 'transport', 'colis'], true))
            ->unique()
            ->values()
            ->all();

        $subscriptions = array_filter([
            'modules' => !empty($modules) ? $modules : ['food', 'transport', 'colis'],
            'audio_enabled' => array_key_exists('audio_enabled', $attributes)
                ? (bool) $attributes['audio_enabled']
                : (bool) data_get($metadata, 'subscriptions.audio_enabled', true),
            'interactive_enabled' => array_key_exists('interactive_enabled', $attributes)
                ? (bool) $attributes['interactive_enabled']
                : (bool) data_get($metadata, 'subscriptions.interactive_enabled', true),
            'realtime_enabled' => array_key_exists('realtime_enabled', $attributes)
                ? (bool) $attributes['realtime_enabled']
                : (bool) data_get($metadata, 'subscriptions.realtime_enabled', true),
        ], static fn ($value) => $value !== null);

        $metadata['subscriptions'] = $subscriptions;

        return $metadata;
    }
}
