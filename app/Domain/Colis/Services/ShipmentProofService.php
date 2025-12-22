<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Models\ShipmentProof;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShipmentProofService
{
    protected string $disk = 'private';

    /**
     * Stocker une preuve (Photo ou Signature)
     */
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

    /**
     * Générer une URL signée temporaire pour visualiser la preuve
     */
    public function getSignedUrl(ShipmentProof $proof): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $proof->storage_path, 
            now()->addMinutes(15)
        );
    }

    /**
     * Générer un OTP de livraison
     */
    public function generateOTP(Shipment $shipment): string
    {
        $otp = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Stocker l'OTP dans les métadonnées du colis pour vérification ultérieure
        // Dans un système réel, on utiliserait un cache Redis ou une table dédiée
        $shipment->update([
            'price_breakdown' => array_merge($shipment->price_breakdown ?? [], ['delivery_otp' => $otp])
        ]);

        return $otp;
    }

    /**
     * Vérifier l'OTP
     */
    public function verifyOTP(Shipment $shipment, string $inputOtp): bool
    {
        $storedOtp = $shipment->price_breakdown['delivery_otp'] ?? null;
        return $storedOtp === $inputOtp;
    }
}

