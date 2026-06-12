<?php

namespace App\Services;

use App\Address;

class AddressQualityService
{
    public function __construct(
        protected CongoAdministrativeHierarchyService $congoAdministrativeHierarchyService
    ) {}

    public function forFood(?Address $savedAddress, array $checkoutData): array
    {
        $addressLine = $savedAddress
            ? trim((string) ($savedAddress->complete_address ?: $savedAddress->street_no))
            : trim((string) ($checkoutData['delivery_address'] ?? ''));

        $district = $savedAddress
            ? trim((string) $savedAddress->area)
            : trim((string) ($checkoutData['delivery_area'] ?? ''));

        $houseNumber = $savedAddress
            ? trim((string) $savedAddress->building_no)
            : null;

        $landmark = trim((string) ($checkoutData['pickup_note'] ?? ''));

        return $this->buildQuality(
            latitude: $savedAddress?->latitude ?? ($checkoutData['d_lat'] ?? null),
            longitude: $savedAddress?->longitude ?? ($checkoutData['d_lng'] ?? null),
            addressLine: $addressLine,
            district: $district,
            city: trim((string) ($checkoutData['delivery_city'] ?? '')),
            department: trim((string) ($checkoutData['delivery_department'] ?? '')),
            houseNumber: $houseNumber,
            landmark: $landmark,
            source: $savedAddress ? 'saved_address' : 'typed_address'
        );
    }

    public function forShipmentAddress(array $address, string $type): array
    {
        return $this->buildQuality(
            latitude: $address['lat'] ?? null,
            longitude: $address['lng'] ?? null,
            addressLine: trim((string) ($address['address_line'] ?? '')),
            district: trim((string) ($address['district'] ?? '')),
            city: trim((string) ($address['city'] ?? '')),
            department: trim((string) ($address['department'] ?? '')),
            houseNumber: $this->extractHouseNumber((string) ($address['address_line'] ?? '')),
            landmark: trim((string) ($address['landmark'] ?? '')),
            source: 'shipment_' . $type
        );
    }

    public function needsExplicitConfirmation(array $quality): bool
    {
        return in_array($quality['level'] ?? 'blind', ['district', 'area', 'blind'], true);
    }

    protected function buildQuality(
        $latitude,
        $longitude,
        string $addressLine,
        string $district = '',
        string $city = '',
        string $department = '',
        ?string $houseNumber = null,
        string $landmark = '',
        string $source = 'declared'
    ): array {
        $coordsPresent = is_numeric($latitude) && is_numeric($longitude);
        $normalizedAddress = preg_replace('/\s+/u', ' ', trim($addressLine)) ?: '';
        $roadPresent = $normalizedAddress !== '' && mb_strlen($normalizedAddress) >= 6;
        $districtPresent = trim($district) !== '';
        $houseNumberPresent = trim((string) $houseNumber) !== '' || $this->containsHouseNumber($normalizedAddress);
        $landmarkPresent = trim($landmark) !== '';
        $administrative = $this->congoAdministrativeHierarchyService->resolve([
            'country' => 'Congo',
            'department' => $department,
            'commune' => $city,
            'city' => $city,
            'district' => $district,
        ]);

        $level = match (true) {
            $coordsPresent && $roadPresent && ($houseNumberPresent || $landmarkPresent) => 'exact',
            $coordsPresent && $roadPresent => 'street',
            $coordsPresent && $districtPresent => 'district',
            $coordsPresent => 'area',
            default => 'blind',
        };

        return [
            'source' => $source,
            'level' => $level,
            'coordinates_present' => $coordsPresent,
            'road_present' => $roadPresent,
            'district_present' => $districtPresent,
            'house_number_present' => $houseNumberPresent,
            'landmark_present' => $landmarkPresent,
            'administrative' => $administrative,
            'requires_manual_confirmation' => in_array($level, ['street', 'district', 'area', 'blind'], true),
        ];
    }

    protected function containsHouseNumber(string $value): bool
    {
        return preg_match('/\b\d{1,5}[A-Za-z\-\/]?\b/u', $value) === 1;
    }

    protected function extractHouseNumber(string $value): ?string
    {
        if (preg_match('/\b(\d{1,5}[A-Za-z\-\/]?)\b/u', $value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
