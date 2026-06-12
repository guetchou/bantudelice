<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Models\ShipmentProof;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShipmentProofService
{
    protected string $disk = 'private';

    public function storeProof(Shipment $shipment, UploadedFile $file, string $type): ShipmentProof
    {
        $filename = "shipment_{$shipment->id}_{$type}_" . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("shipments/proofs/{$shipment->id}", $filename, $this->disk);

        return $shipment->proofs()->create([
            'type' => $type,
            'storage_path' => $path,
            'hash' => hash_file('sha256', $file->getRealPath()),
        ]);
    }

    public function getSignedUrl(ShipmentProof $proof): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $proof->storage_path,
            now()->addMinutes(15)
        );
    }

    public function generateOTP(Shipment $shipment): string
    {
        if (
            !empty($shipment->delivery_otp_code) &&
            !empty($shipment->delivery_otp_expires_at) &&
            $shipment->delivery_otp_expires_at->isFuture()
        ) {
            return (string) $shipment->delivery_otp_code;
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        if (Schema::hasColumn('shipments', 'delivery_otp_code') && Schema::hasColumn('shipments', 'delivery_otp_expires_at')) {
            $shipment->update([
                'delivery_otp_code' => $otp,
                'delivery_otp_expires_at' => now()->addHours(12),
            ]);

            return $otp;
        }

        $shipment->update([
            'price_breakdown' => array_merge($shipment->price_breakdown ?? [], ['delivery_otp' => $otp]),
        ]);

        return $otp;
    }

    public function verifyOTP(Shipment $shipment, ?string $inputOtp): bool
    {
        if (Schema::hasColumn('shipments', 'delivery_otp_code') && !empty($shipment->delivery_otp_code)) {
            if (empty($inputOtp)) {
                return false;
            }

            if ($shipment->delivery_otp_expires_at && $shipment->delivery_otp_expires_at->isPast()) {
                return false;
            }

            return hash_equals((string) $shipment->delivery_otp_code, trim((string) $inputOtp));
        }

        $storedOtp = $shipment->price_breakdown['delivery_otp'] ?? null;
        if (!$storedOtp) {
            return false;
        }

        return hash_equals((string) $storedOtp, trim((string) $inputOtp));
    }
}
