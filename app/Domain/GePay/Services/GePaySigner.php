<?php

namespace App\Domain\GePay\Services;

final class GePaySigner
{
    public static function canonical(string $timestamp, string $method, string $uri, string $body): string
    {
        return implode("\n", [
            $timestamp,
            strtoupper($method),
            $uri,
            hash('sha256', $body),
        ]);
    }

    public static function sign(string $secret, string $timestamp, string $method, string $uri, string $body): string
    {
        return hash_hmac('sha256', self::canonical($timestamp, $method, $uri, $body), $secret);
    }
}
