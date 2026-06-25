<?php

namespace App\Services;

use App\Driver;
use App\DriverLocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DriverLocationIngestionService
{
    private const STALE_TOLERANCE_SECONDS = 5;
    private const FUTURE_TOLERANCE_SECONDS = 120;
    private const COORDINATE_EPSILON = 0.0000001;

    public function __construct(
        private MissionPresenceBroadcastService $presenceBroadcasts
    ) {
    }

    /**
     * Persist one GPS sample without allowing an old/retried sample to move the
     * driver's current position backwards in time.
     *
     * @return array{accepted:bool, duplicate:bool, stale:bool, location:?DriverLocation, driver:Driver}
     */
    public function ingest(
        Driver $driver,
        array $payload,
        bool $markOnline = true,
        bool $broadcast = true
    ): array {
        $recordedAt = $this->resolveRecordedAt($payload['recorded_at'] ?? null);

        $result = DB::transaction(function () use ($driver, $payload, $recordedAt, $markOnline): array {
            /** @var Driver $lockedDriver */
            $lockedDriver = Driver::query()->lockForUpdate()->findOrFail($driver->id);

            /** @var DriverLocation|null $latest */
            $latest = DriverLocation::query()
                ->where('driver_id', $lockedDriver->id)
                ->orderByDesc('timestamp')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($latest && $recordedAt->lt($latest->timestamp->copy()->subSeconds(self::STALE_TOLERANCE_SECONDS))) {
                return [
                    'accepted' => false,
                    'duplicate' => false,
                    'stale' => true,
                    'location' => $latest,
                    'driver' => $lockedDriver,
                ];
            }

            if ($latest && $this->isDuplicate($latest, $payload, $recordedAt)) {
                return [
                    'accepted' => false,
                    'duplicate' => true,
                    'stale' => false,
                    'location' => $latest,
                    'driver' => $lockedDriver,
                ];
            }

            $location = DriverLocation::create([
                'driver_id' => $lockedDriver->id,
                'latitude' => (float) $payload['latitude'],
                'longitude' => (float) $payload['longitude'],
                'accuracy' => $this->nullableFloat($payload['accuracy'] ?? null),
                'heading' => $this->nullableFloat($payload['heading'] ?? null),
                'speed' => $this->nullableFloat($payload['speed'] ?? null),
                'timestamp' => $recordedAt,
            ]);

            $isNewest = ! $latest || $recordedAt->gte($latest->timestamp);
            if ($isNewest) {
                $updates = [
                    'latitude' => (float) $payload['latitude'],
                    'longitude' => (float) $payload['longitude'],
                ];

                if ($markOnline) {
                    $updates['status'] = 'online';
                }

                $lockedDriver->forceFill($updates)->save();
            }

            return [
                'accepted' => true,
                'duplicate' => false,
                'stale' => false,
                'location' => $location,
                'driver' => $lockedDriver->fresh(),
            ];
        });

        if ($broadcast && $result['accepted']) {
            $this->presenceBroadcasts->broadcastForDriver($result['driver']);
        }

        return $result;
    }

    private function resolveRecordedAt(mixed $value): CarbonImmutable
    {
        $now = CarbonImmutable::now();
        if ($value === null || $value === '') {
            return $now;
        }

        $recordedAt = CarbonImmutable::parse($value);
        if ($recordedAt->gt($now->addSeconds(self::FUTURE_TOLERANCE_SECONDS))) {
            return $now;
        }

        return $recordedAt;
    }

    private function isDuplicate(DriverLocation $latest, array $payload, CarbonImmutable $recordedAt): bool
    {
        return abs($latest->timestamp->diffInSeconds($recordedAt, false)) <= 1
            && abs((float) $latest->latitude - (float) $payload['latitude']) <= self::COORDINATE_EPSILON
            && abs((float) $latest->longitude - (float) $payload['longitude']) <= self::COORDINATE_EPSILON;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
