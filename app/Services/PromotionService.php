<?php

namespace App\Services;

use App\Order;
use App\Restaurant;
use App\User;
use App\Voucher;
use App\VoucherRedemption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PromotionService
{
    public function preview(string $code, ?Restaurant $restaurant = null, ?User $user = null, float $subtotal = 0.0): array
    {
        $evaluation = $this->evaluateVoucher($code, $restaurant, $user, $subtotal);

        if (!$evaluation['valid']) {
            return $evaluation;
        }

        /** @var Voucher $voucher */
        $voucher = $evaluation['voucher'];
        $discount = $this->calculateDiscount($voucher, $subtotal);

        return [
            'valid' => true,
            'voucher' => $voucher,
            'discount' => $discount,
            'message' => 'Code promo valide !',
            'rules' => $evaluation['rules'] ?? [],
            'remaining_usage' => $evaluation['remaining_usage'] ?? null,
            'remaining_user_usage' => $evaluation['remaining_user_usage'] ?? null,
        ];
    }

    public function resolveVoucher(string $code, ?Restaurant $restaurant = null, ?User $user = null, float $subtotal = 0.0): ?Voucher
    {
        $evaluation = $this->evaluateVoucher($code, $restaurant, $user, $subtotal);

        return $evaluation['valid'] ? $evaluation['voucher'] : null;
    }

    public function calculateDiscount(Voucher $voucher, float $subtotal): float
    {
        $discountType = strtolower((string) ($voucher->discount_type ?? 'percentage'));
        $baseValue = (float) ($voucher->discount_value ?? $voucher->discount ?? 0);

        if ($discountType === 'fixed') {
            $discount = min($subtotal, max(0, $baseValue));
        } else {
            $ratio = max(0, min(100, $baseValue)) / 100;
            $discount = $subtotal * $ratio;
        }

        $configuredMax = (float) config('commerce.max_promo_discount_ratio', 0.2);
        $maxDiscountAmount = $voucher->max_discount_amount !== null
            ? (float) $voucher->max_discount_amount
            : ($subtotal * $configuredMax);

        $discount = min($discount, $maxDiscountAmount);

        return round(max(0, $discount), 2);
    }

    public function redeem(?Voucher $voucher, ?Order $order, ?User $user, float $subtotal, float $discount, array $payload = []): ?VoucherRedemption
    {
        if (!$voucher || !Schema::hasTable('voucher_redemptions')) {
            return null;
        }

        return DB::transaction(function () use ($voucher, $order, $user, $subtotal, $discount, $payload) {
            $current = Voucher::query()->lockForUpdate()->find($voucher->id);
            if (!$current) {
                throw new \InvalidArgumentException('Code promo invalide ou expiré');
            }

            $evaluation = $this->evaluateVoucher((string) $current->name, $order?->restaurant, $user, $subtotal, $current);
            if (!$evaluation['valid']) {
                throw new \InvalidArgumentException($evaluation['message'] ?? 'Code promo invalide ou expiré');
            }

            $current->forceFill([
                'used_count' => (int) ($current->used_count ?? 0) + 1,
            ])->save();

            return VoucherRedemption::create([
                'voucher_id' => $current->id ?? null,
                'voucher_code' => $current->name,
                'user_id' => $user?->id,
                'restaurant_id' => $order?->restaurant_id ?? $current->restaurant_id,
                'order_id' => $order?->id,
                'order_no' => $order?->order_no,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'discount_type' => $current->discount_type ?? 'percentage',
                'discount_rate' => $current->discount_value ?? $current->discount,
                'discount_cap' => $current->max_discount_amount,
                'idempotency_key' => $payload['idempotency_key'] ?? ($order?->order_no ? 'order:' . $order->order_no : null),
                'status' => 'redeemed',
                'redeemed_at' => now(),
                'details' => $payload,
                'payload' => $payload,
            ]);
        });
    }

    public function releaseByOrder(string $orderNo, string $reason = 'order_cancelled'): int
    {
        if (!Schema::hasTable('voucher_redemptions')) {
            return 0;
        }

        return DB::transaction(function () use ($orderNo, $reason) {
            $redemptions = VoucherRedemption::where('order_no', $orderNo)
                ->where('status', 'redeemed')
                ->get();

            if ($redemptions->isEmpty()) {
                return 0;
            }

            $countsByVoucher = $redemptions
                ->groupBy('voucher_id')
                ->map(fn ($items) => $items->count());

            VoucherRedemption::whereIn('id', $redemptions->pluck('id')->all())
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                    'details' => ['reason' => $reason],
                    'payload' => ['reason' => $reason],
                ]);

            foreach ($countsByVoucher as $voucherId => $count) {
                if (!$voucherId) {
                    continue;
                }

                $voucher = Voucher::query()->lockForUpdate()->find($voucherId);
                if (!$voucher) {
                    continue;
                }

                $voucher->forceFill([
                    'used_count' => max(0, (int) ($voucher->used_count ?? 0) - (int) $count),
                ])->save();
            }

            return $redemptions->count();
        });
    }

    protected function evaluateVoucher(string $code, ?Restaurant $restaurant = null, ?User $user = null, float $subtotal = 0.0, ?Voucher $voucherModel = null): array
    {
        $code = trim($code);
        if ($code === '') {
            return $this->invalidVoucherResult('Code promo invalide ou expiré');
        }

        $voucher = $voucherModel ?: Voucher::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($code)])
            ->first();

        if (!$voucher) {
            return $this->invalidVoucherResult('Code promo invalide ou expiré');
        }

        if (Schema::hasColumn('vouchers', 'is_active') && !((bool) $voucher->is_active)) {
            return $this->invalidVoucherResult('Code promo inactif');
        }

        $now = Carbon::now();
        $startsAt = $this->parseDate($voucher->starts_at ?? $voucher->start_date ?? null);
        $endsAt = $this->parseDate($voucher->ends_at ?? $voucher->end_date ?? null);

        if ($startsAt && $startsAt->gt($now)) {
            return $this->invalidVoucherResult('Code promo pas encore disponible');
        }

        if ($endsAt && $endsAt->lt($now)) {
            return $this->invalidVoucherResult('Code promo expiré');
        }

        if ($restaurant && $voucher->restaurant_id && (int) $voucher->restaurant_id !== (int) $restaurant->id) {
            return $this->invalidVoucherResult('Code promo réservé à un autre restaurant');
        }

        $minimum = (float) ($voucher->min_order_amount ?? 0);
        if ($minimum > 0 && $subtotal < $minimum) {
            return $this->invalidVoucherResult('Montant minimum requis: ' . number_format($minimum, 0, ',', ' ') . ' FCFA');
        }

        $usageLimit = $voucher->usage_limit !== null ? (int) $voucher->usage_limit : null;
        $usedCount = (int) ($voucher->used_count ?? 0);
        if ($usageLimit !== null && $usedCount >= $usageLimit) {
            return $this->invalidVoucherResult('Quota du code promo épuisé');
        }

        $perUserLimit = (int) ($voucher->per_user_limit ?? 0);
        $userRedemptions = 0;
        if ($user && Schema::hasTable('voucher_redemptions') && $perUserLimit > 0) {
            $userRedemptions = VoucherRedemption::where('voucher_id', $voucher->id)
                ->where('user_id', $user->id)
                ->where('status', 'redeemed')
                ->count();

            if ($userRedemptions >= $perUserLimit) {
                return $this->invalidVoucherResult('Vous avez déjà utilisé ce code promo');
            }
        }

        $rules = [
            'discount_type' => $voucher->discount_type ?? 'percentage',
            'discount_value' => (float) ($voucher->discount_value ?? $voucher->discount ?? 0),
            'min_order_amount' => $minimum,
            'max_discount_amount' => $voucher->max_discount_amount,
            'usage_limit' => $usageLimit,
            'used_count' => $usedCount,
            'remaining_usage' => $usageLimit !== null ? max(0, $usageLimit - $usedCount) : null,
            'per_user_limit' => $perUserLimit,
            'remaining_user_usage' => $perUserLimit > 0 ? max(0, $perUserLimit - $userRedemptions) : null,
            'stackable' => (bool) ($voucher->stackable ?? false),
            'restaurant_id' => $voucher->restaurant_id,
            'starts_at' => $startsAt?->toIso8601String(),
            'ends_at' => $endsAt?->toIso8601String(),
        ];

        return [
            'valid' => true,
            'voucher' => $voucher,
            'message' => 'Code promo valide !',
            'rules' => $rules,
            'remaining_usage' => $rules['remaining_usage'],
            'remaining_user_usage' => $rules['remaining_user_usage'],
        ];
    }

    protected function invalidVoucherResult(string $message): array
    {
        return [
            'valid' => false,
            'voucher' => null,
            'discount' => 0,
            'message' => $message,
            'rules' => [],
            'remaining_usage' => null,
            'remaining_user_usage' => null,
        ];
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
