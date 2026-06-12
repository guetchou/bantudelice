<?php

namespace App\Services;

use App\Driver;
use App\DriverToken;
use App\Order;
use App\UserToken;
use App\Services\CommerceSignalService;
use App\Services\FcmTransport;
use App\Services\FoodOrderNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Façade de notification : résout les tokens, dispatche vers FcmTransport,
 * logue les envois. La logique métier food est dans FoodOrderNotificationService.
 * La couche FCM brute est dans FcmTransport.
 * Toutes les méthodes statiques publiques sont conservées pour la compatibilité.
 */
class NotificationService
{
    // ── Façades FCM (délèguent à FcmTransport) ────────────────────────────────

    public static function sendToDevice($deviceToken, $title, $body, $key, $userId = null, $type = 'user', array $dataExtra = []): array
    {
        return FcmTransport::sendToDevice($deviceToken, $title, $body, $key, $userId, $type, $dataExtra);
    }

    public static function sendToMultipleDevices($deviceTokens, $title, $body, $key, $userId = null, $type = 'user', array $dataExtra = []): array
    {
        return FcmTransport::sendToMultipleDevices($deviceTokens, $title, $body, $key, $userId, $type, $dataExtra);
    }

    public static function sendWithAction($deviceToken, $title, $body, $key, $clickAction, array $dataExtra = []): array
    {
        return FcmTransport::sendWithAction($deviceToken, $title, $body, $key, $clickAction, $dataExtra);
    }

    public static function hasConfiguredFcmKey(?string $type = null): bool
    {
        return FcmTransport::hasConfiguredKey($type ?? 'user');
    }

    // ── Envoi par destinataire (résolution tokens + dispatch) ─────────────────

    public static function sendToUser($userId, $title, $body, array $extra = []): array
    {
        $tokenRecords = self::activeTokenRecords(UserToken::class, 'user_id', $userId, $extra['module'] ?? null);

        if ($tokenRecords->isEmpty()) {
            Log::info("Aucun token trouvé pour l'utilisateur {$userId}");
            self::notificationLogs()->record([
                'channel'        => 'push',
                'recipient_type' => $extra['channel'] ?? 'user',
                'recipient_id'   => $userId,
                'title'          => $title,
                'body'           => $body,
                'provider'       => 'fcm',
                'status'         => 'no_tokens',
                'context'        => $extra,
            ]);
            app(CommerceSignalService::class)->emit('notification.no_tokens', [
                'domain'         => 'commerce',
                'module'         => $extra['module'] ?? 'global',
                'severity'       => 'warning',
                'recipient_type' => $extra['channel'] ?? 'user',
                'recipient_id'   => $userId,
                'title'          => $title,
            ]);
            return ['success' => false, 'error' => 'no_tokens'];
        }

        $result = self::sendToTokenRecords(
            $tokenRecords,
            $title,
            $body,
            $extra['key'] ?? ($extra['type'] ?? 'general'),
            $userId,
            $extra['channel'] ?? 'user',
            $extra
        );

        self::notificationLogs()->record([
            'channel'        => 'push',
            'recipient_type' => $extra['channel'] ?? 'user',
            'recipient_id'   => $userId,
            'title'          => $title,
            'body'           => $body,
            'provider'       => 'fcm',
            'status'         => !empty($result['success']) ? 'sent' : 'failed',
            'context'        => array_merge($extra, ['response' => $result]),
        ]);
        app(CommerceSignalService::class)->emit('notification.sent', [
            'domain'         => 'commerce',
            'module'         => $extra['module'] ?? 'global',
            'severity'       => !empty($result['success']) ? 'info' : 'warning',
            'recipient_type' => $extra['channel'] ?? 'user',
            'recipient_id'   => $userId,
            'title'          => $title,
            'status'         => !empty($result['success']) ? 'sent' : 'failed',
            'provider'       => 'fcm',
        ]);

        return $result;
    }

    public static function sendToDriver($driverId, $title, $body, array $extra = []): array
    {
        $tokenRecords = self::activeTokenRecords(DriverToken::class, 'driver_id', $driverId, $extra['module'] ?? null);

        if ($tokenRecords->isEmpty()) {
            Log::info("Aucun token trouvé pour le livreur {$driverId}");
            self::notificationLogs()->record([
                'channel'        => 'push',
                'recipient_type' => 'driver',
                'recipient_id'   => $driverId,
                'title'          => $title,
                'body'           => $body,
                'provider'       => 'fcm',
                'status'         => 'no_tokens',
                'context'        => $extra,
            ]);
            app(CommerceSignalService::class)->emit('notification.no_tokens', [
                'domain'         => 'commerce',
                'module'         => $extra['module'] ?? 'global',
                'severity'       => 'warning',
                'recipient_type' => 'driver',
                'recipient_id'   => $driverId,
                'title'          => $title,
            ]);
            return ['success' => false, 'error' => 'no_tokens'];
        }

        $result = self::sendToTokenRecords(
            $tokenRecords,
            $title,
            $body,
            $extra['key'] ?? 'driver_update',
            $driverId,
            'driver',
            $extra
        );

        self::notificationLogs()->record([
            'channel'        => 'push',
            'recipient_type' => 'driver',
            'recipient_id'   => $driverId,
            'title'          => $title,
            'body'           => $body,
            'provider'       => 'fcm',
            'status'         => !empty($result['success']) ? 'sent' : 'failed',
            'context'        => array_merge($extra, ['response' => $result]),
        ]);
        app(CommerceSignalService::class)->emit('notification.sent', [
            'domain'         => 'commerce',
            'module'         => $extra['module'] ?? 'global',
            'severity'       => !empty($result['success']) ? 'info' : 'warning',
            'recipient_type' => 'driver',
            'recipient_id'   => $driverId,
            'title'          => $title,
            'status'         => !empty($result['success']) ? 'sent' : 'failed',
            'provider'       => 'fcm',
        ]);

        return $result;
    }

    // ── Notification food commande (délègue à FoodOrderNotificationService) ──

    public function notifyFoodOrderStatusChange(Order $order, string $businessStatus, array $context = []): void
    {
        app(FoodOrderNotificationService::class)->notifyStatusChange($order, $businessStatus, $context);
    }

    // ── Utilitaire routes ────────────────────────────────────────────────────

    public static function routePath(string $routeName, array $parameters = []): string
    {
        try {
            $url  = route($routeName, $parameters);
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            return $query ? "{$path}?{$query}" : $path;
        } catch (\Throwable $e) {
            return '/';
        }
    }

    // ── Préférences de livraison ─────────────────────────────────────────────

    public static function applyDeliveryPreferences(array $extra, $metadata = null): array
    {
        $metadata      = self::normalizeMetadataArray($metadata);
        $subscriptions = is_array($metadata) ? ($metadata['subscriptions'] ?? []) : [];

        if (($subscriptions['audio_enabled'] ?? true) === false) {
            unset($extra['sound_key'], $extra['audio_cue']);
        }
        if (($subscriptions['interactive_enabled'] ?? true) === false) {
            unset($extra['actions']);
        }
        if (($subscriptions['realtime_enabled'] ?? true) === false) {
            unset($extra['websocket_channel'], $extra['websocket_event'], $extra['tracking_channel'], $extra['presence_channel']);
        }

        return $extra;
    }

    // ── Helpers tokens ────────────────────────────────────────────────────────

    protected static function activeTokenRecords(string $modelClass, string $ownerColumn, int $ownerId, ?string $module = null)
    {
        $query   = $modelClass::query()->where($ownerColumn, $ownerId);
        $table   = (new $modelClass())->getTable();
        $siteKey = self::currentSiteKey();

        if (Schema::hasColumn($table, 'active')) {
            $query->where(function ($builder) {
                $builder->whereNull('active')->orWhere('active', true);
            });
        }

        if (Schema::hasColumn($table, 'site_key')) {
            $query->where(function ($builder) use ($siteKey) {
                $builder->whereNull('site_key')->orWhere('site_key', $siteKey);
            });
        }

        $records = $query->get(['device_tokens', 'metadata']);

        return $records->filter(function ($record) use ($module) {
            if ($module === null || $module === '') {
                return true;
            }
            $metadata = self::normalizeMetadataArray($record->metadata);
            if (!is_array($metadata) || empty($metadata)) {
                return true;
            }
            $modules = data_get($metadata, 'subscriptions.modules', []);
            if (!is_array($modules) || empty($modules)) {
                return true;
            }
            return in_array($module, $modules, true);
        })->values();
    }

    protected static function sendToTokenRecords($tokenRecords, string $title, string $body, string $key, $userId, string $type, array $extra = []): array
    {
        $results = [];

        foreach ($tokenRecords as $record) {
            $shapedExtra = self::applyDeliveryPreferences($extra, $record->metadata);
            $results[]   = FcmTransport::sendToDevice(
                $record->device_tokens,
                $title,
                $body,
                $key,
                $userId,
                $type,
                $shapedExtra
            );
        }

        $successes = collect($results)->filter(fn ($r) => !empty($r['success']))->count();

        return [
            'success'              => $successes > 0,
            'error'                => $successes > 0 ? null : ($results[0]['error'] ?? 'dispatch_failed'),
            'dispatches'           => count($results),
            'successful_dispatches' => $successes,
            'data'                 => $results,
        ];
    }

    protected static function currentSiteKey(): string
    {
        try {
            return app(SiteContextService::class)->currentSiteKey();
        } catch (\Throwable $e) {
            return config('sites.default_site', 'main');
        }
    }

    protected static function normalizeMetadataArray($metadata): array
    {
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }
        return is_array($metadata) ? $metadata : [];
    }

    protected static function notificationLogs(): NotificationLogService
    {
        return app(NotificationLogService::class);
    }
}
