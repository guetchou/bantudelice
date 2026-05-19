<?php

namespace App\Services;

use App\Delivery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

/**
 * Responsabilité unique : OTP de remise et preuves de livraison (photo, confirmation).
 */
class DeliveryProofService
{
    public function ensureDeliveryOtp(Delivery $delivery): Delivery
    {
        if (!Schema::hasColumn('deliveries', 'delivery_otp_code') || !Schema::hasColumn('deliveries', 'delivery_otp_expires_at')) {
            return $delivery;
        }

        if (!empty($delivery->delivery_otp_code) && $delivery->delivery_otp_expires_at && $delivery->delivery_otp_expires_at->isFuture()) {
            return $delivery;
        }

        $delivery->update([
            'delivery_otp_code' => (string) random_int(1000, 9999),
            'delivery_otp_expires_at' => now()->addHours(6),
            'otp_verified_at' => null,
        ]);

        return $delivery->fresh();
    }

    public function verifyDeliveryOtp(Delivery $delivery, ?string $otp): bool
    {
        if (empty($delivery->delivery_otp_code)) {
            return true;
        }

        if (empty($otp)) {
            return false;
        }

        if ($delivery->delivery_otp_expires_at && $delivery->delivery_otp_expires_at->isPast()) {
            return false;
        }

        return hash_equals((string) $delivery->delivery_otp_code, trim((string) $otp));
    }

    public function storeProofFile(UploadedFile $file, string $prefix = 'delivery'): string
    {
        $directory = public_path('images/delivery_proofs');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = $prefix . '_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'images/delivery_proofs/' . $filename;
    }

    public function assertDeliveryProofOrConfirmation(Delivery $delivery, array $context): void
    {
        $hasOtp = $this->verifyDeliveryOtp($delivery, $context['delivery_otp'] ?? null);
        $hasCustomerConfirmation = !empty($context['customer_confirmed']);
        $hasProof = !empty($context['delivery_proof_path']) || !empty($delivery->delivery_proof_path);

        if (!$hasOtp && !$hasCustomerConfirmation && !$hasProof) {
            throw new \RuntimeException('La remise nécessite un OTP valide, une confirmation client ou une preuve de livraison.');
        }
    }

    public function resolveConfirmationMethod(array $context): ?string
    {
        if (!empty($context['delivery_otp'])) {
            return 'otp';
        }

        if (!empty($context['customer_confirmed'])) {
            return 'customer_button';
        }

        if (!empty($context['delivery_proof_path'])) {
            return 'photo';
        }

        return null;
    }
}
