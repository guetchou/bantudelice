<?php

namespace App\Services;

use App\Driver;
use App\DriverLocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverLocationIngestionService
{
    private const STALE_TOLERANCE_SECONDS = 5;
    private const MAX_SAMPLE_AGE_SECONDS = 300;
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

            if ($recordedAt->lt(CarbonImmutable::now()->subSeconds(self::MAX_SAMPLE_AGE_SECONDS))) {
                return $this->rejectedResult($lockedDriver, $latest, stale: true);
            }

            if ($latest && $recordedAt->lt($latest->timestamp->copy()->subSeconds(self::STALE_TOLERANCE_SECONDS))) {
                return $this->rejectedResult($lockedDriver, $latest, stale: true);
            }

            if ($latest && $this->isDuplicate($latest, $payload, $recordedAt)) {
                return $this->rejectedResult($lockedDriver, $latest, duplicate: true);
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
            try {
                $this->presenceBroadcasts->broadcastForDriver($result['driver']);
            } catch (\Throwable $exception) {
                Log::warning('Realtime presence broadcast failed after GPS persistence', [
                    'driver_id' => $result['driver']->id,
                    'location_id' => $result['location']?->id,
                    'error' => $exception->getMessage(),
                ]);
            }
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

    /**
     * @return array{accepted:false, duplicate:bool, stale:bool, location:?DriverLocation, driver:Driver}
     */
    private function rejectedResult(
        Driver $driver,
        ?DriverLocation $latest,
        bool $duplicate = false,
        bool $stale = false
    ): array {
        return [
            'accepted' => false,
            'duplicate' => $duplicate,
            'stale' => $stale,
            'location' => $latest,
            'driver' => $driver,
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
