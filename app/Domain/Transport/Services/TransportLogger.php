<?php

namespace App\Domain\Transport\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransportLogger
{
    /**
     * Log a transport event with correlation ID
     */
    public static function info(string $message, array $context = [])
    {
        $correlationId = $context['correlation_id'] ?? (string) Str::uuid();
        
        Log::channel('daily')->info("[TRANSPORT][{$correlationId}] " . $message, array_merge($context, [
            'correlation_id' => $correlationId,
            'module' => 'transport',
            'timestamp' => now()->toIso8601String()
        ]));
    }

    /**
     * Log a transport error
     */
    public static function error(string $message, \Exception $e = null, array $context = [])
    {
        $correlationId = $context['correlation_id'] ?? (string) Str::uuid();

        Log::channel('daily')->error("[TRANSPORT_ERROR][{$correlationId}] " . $message, array_merge($context, [
            'correlation_id' => $correlationId,
            'module' => 'transport',
            'error' => $e ? $e->getMessage() : null,
            'trace' => $e ? $e->getTraceAsString() : null
        ]));
    }
}

