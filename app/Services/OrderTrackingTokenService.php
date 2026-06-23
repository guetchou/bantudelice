<?php

namespace App\Services;

use App\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderTrackingTokenService
{
    private const DEFAULT_TTL_DAYS = 30;

    public function generateForOrder(Order $order, ?CarbonInterface $expiresAt = null): string
    {
        $this->assertColumnsExist();

        $plainKey = Str::random(64);
        $hash = $this->hashToken($plainKey);
        $expiresAt = $expiresAt ?? now()->addDays(self::DEFAULT_TTL_DAYS);

        DB::table('orders')->where('order_no', $order->order_no)->update([
            'tracking_token_hash' => $hash,
            'tracking_token_expires_at' => $expiresAt,
            'tracking_token_last_used_at' => null,
            'tracking_token_revoked_at' => null,
            'updated_at' => now(),
        ]);

        return $plainKey;
    }

    public function rotate(Order $order, ?CarbonInterface $expiresAt = null): string
    {
        return $this->generateForOrder($order, $expiresAt);
    }

    public function publicUrlForOrder(Order $order): string
    {
        return route('track.order.guest', [$this->rotate($order)]);
    }

    public function hashToken(string $plainKey): string
    {
        return hash('sha256', trim($plainKey));
    }

    public function resolveValidToken(string $plainKey): ?Order
    {
        $this->assertColumnsExist();

        $plainKey = trim($plainKey);
        if (strlen($plainKey) < 48) {
            return null;
        }

        $order = Order::where('tracking_token_hash', $this->hashToken($plainKey))->first();

        if (! $order || $this->isExpired($order) || $order->tracking_token_revoked_at !== null) {
            return null;
        }

        DB::table('orders')->where('order_no', $order->order_no)->update([
            'tracking_token_last_used_at' => now(),
        ]);

        return $order->fresh();
    }

    public function isExpired(Order $order): bool
    {
        if ($order->tracking_token_expires_at === null) {
            return false;
        }

        return \Carbon\Carbon::parse($order->tracking_token_expires_at)->isPast();
    }

    private function assertColumnsExist(): void
    {
        if (! Schema::hasColumn('orders', 'tracking_token_hash')) {
            throw new \RuntimeException('Les champs de tracking invité ne sont pas migrés.');
        }
    }
}
