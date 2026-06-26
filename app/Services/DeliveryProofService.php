<?php

namespace App\Services;

use App\Delivery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * OTP client et preuves de remise.
 * Le livreur ne peut jamais produire lui-même une confirmation client.
 */
class DeliveryProofService
{
    private const OTP_TTL_MINUTES = 30;
    private const OTP_MAX_ATTEMPTS = 5;

    public function ensureDeliveryOtp(Delivery $delivery): Delivery
    {
        if (! in_array($delivery->status, ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'], true)) {
            return $delivery;
        }

        if (! Schema::hasColumn('deliveries', 'delivery_otp_code')
            || ! Schema::hasColumn('deliveries', 'delivery_otp_expires_at')) {
            return $delivery;
        }

        $hasValidHashedCode = $this->isHashedCode((string) $delivery->delivery_otp_code)
            && $delivery->delivery_otp_expires_at
            && $delivery->delivery_otp_expires_at->isFuture()
            && (int) ($delivery->delivery_otp_attempts ?? 0) < self::OTP_MAX_ATTEMPTS;

        if ($hasValidHashedCode) {
            return $delivery;
        }

        $code = (string) random_int(100000, 999999);
        $payload = [
            'delivery_otp_code' => Hash::make($code),
            'delivery_otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'otp_verified_at' => null,
        ];

        if (Schema::hasColumn('deliveries', 'delivery_otp_attempts')) {
            $payload['delivery_otp_attempts'] = 0;
        }
        if (Schema::hasColumn('deliveries', 'delivery_otp_last_attempt_at')) {
            $payload['delivery_otp_last_attempt_at'] = null;
        }

        $delivery->update($payload);
        $this->notifyCustomer($delivery->fresh(), $code);

        return $delivery->fresh();
    }

    public function verifyDeliveryOtp(Delivery $delivery, ?string $otp): bool
    {
        if (empty($otp)) {
            return false;
        }

        return DB::transaction(function () use ($delivery, $otp): bool {
            $locked = Delivery::query()->lockForUpdate()->find($delivery->id);

            if (! $locked
                || ! $this->isHashedCode((string) $locked->delivery_otp_code)
                || ! $locked->delivery_otp_expires_at
                || $locked->delivery_otp_expires_at->isPast()) {
                return false;
            }

            $attempts = (int) ($locked->delivery_otp_attempts ?? 0);
            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                return false;
            }

            $attempts++;
            $updates = [];
            if (Schema::hasColumn('deliveries', 'delivery_otp_attempts')) {
                $updates['delivery_otp_attempts'] = $attempts;
            }
            if (Schema::hasColumn('deliveries', 'delivery_otp_last_attempt_at')) {
                $updates['delivery_otp_last_attempt_at'] = now();
            }
            if ($updates !== []) {
                $locked->update($updates);
            }

            return Hash::check(trim((string) $otp), (string) $locked->delivery_otp_code);
        }, 3);
    }

    public function storeProofFile(UploadedFile $file, string $prefix = 'delivery'): string
    {
        $directory = public_path('images/delivery_proofs');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = $prefix . '_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'images/delivery_proofs/' . $filename;
    }

    /**
     * @return string otp|customer_button|photo_geolocated
     */
    public function assertDeliveryProofOrConfirmation(Delivery $delivery, array $context): string
    {
        if ($this->verifyDeliveryOtp($delivery, $context['delivery_otp'] ?? null)) {
            return 'otp';
        }

        if (! empty($delivery->customer_confirmed_at)) {
            return 'customer_button';
        }

        $proofPath = $context['delivery_proof_path'] ?? $delivery->delivery_proof_path;
        $latitude = $context['delivery_latitude'] ?? $delivery->delivery_latitude;
        $longitude = $context['delivery_longitude'] ?? $delivery->delivery_longitude;

        if (! empty($proofPath) && $latitude !== null && $longitude !== null) {
            return 'photo_geolocated';
        }

        throw new \RuntimeException(
            'La remise exige un OTP client valide, une confirmation depuis le compte client, ou une photo géolocalisée.'
        );
    }

    /**
     * Compatible avec l'ancien appel resolveConfirmationMethod($context)
     * et le nouvel appel resolveConfirmationMethod($delivery, $context).
     */
    public function resolveConfirmationMethod($deliveryOrContext, array $context = []): ?string
    {
        $delivery = $deliveryOrContext instanceof Delivery ? $deliveryOrContext : null;
        if (is_array($deliveryOrContext)) {
            $context = $deliveryOrContext;
        }

        if ($delivery && ! empty($delivery->otp_verified_at)) {
            return 'otp';
        }

        if ($delivery && ! empty($delivery->customer_confirmed_at)) {
            return 'customer_button';
        }

        if (! empty($context['delivery_proof_path'])
            && array_key_exists('delivery_latitude', $context)
            && array_key_exists('delivery_longitude', $context)) {
            return 'photo_geolocated';
        }

        return null;
    }

    private function notifyCustomer(Delivery $delivery, string $code): void
    {
        $order = $delivery->order()->with('user')->first();
        if (! $order) {
            return;
        }

        $message = "Code de remise de la commande #{$order->order_no} : {$code}. Ne le communiquez au livreur qu’au moment de recevoir la commande. Valide "
            . self::OTP_TTL_MINUTES . ' minutes.';

        try {
            if ($order->user_id) {
                NotificationService::sendToUser($order->user_id, 'Code de remise BantuDelice', $message, [
                    'key' => 'food_delivery_otp',
                    'module' => 'food',
                    'channel' => 'user',
                    'order_no' => $order->order_no,
                    'dedup_key' => 'food:delivery-otp:' . $delivery->id . ':' . $delivery->delivery_otp_expires_at?->timestamp,
                ]);
            }

            if (! empty($order->user?->phone)) {
                SmsService::send($order->user->phone, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('DeliveryProofService: envoi OTP client échoué', [
                'delivery_id' => $delivery->id,
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isHashedCode(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$2a$')
            || str_starts_with($value, '$2b$')
            || str_starts_with($value, '$argon');
    }
}
