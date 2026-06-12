<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TransportGeoService
{
    public function __construct(
        protected CongoAdministrativeHierarchyService $congoAdministrativeHierarchyService
    ) {}

    public function geocodeSearch(string $query, int $limit = 5): array
    {
        $limit = max(1, min($limit, 8));
        $localResults = $this->searchLocalDirectory($query, $limit);

        if (count($localResults) >= $limit) {
            return [
                'data' => array_slice($localResults, 0, $limit),
                'meta' => $this->buildGeocodeMeta($query, $localResults, [], $localResults),
            ];
        }

        $response = Http::timeout(12)
            ->withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'fr',
                'User-Agent' => 'Kende Transport/1.0',
            ])
            ->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'jsonv2',
                'limit' => $limit,
                'countrycodes' => 'cg',
                'addressdetails' => 1,
                'dedupe' => 1,
                'q' => $query,
            ]);

        if (! $response->successful()) {
            return [
                'data' => $localResults,
                'meta' => $this->buildGeocodeMeta($query, $localResults, [], $localResults),
            ];
        }

        $remoteResults = collect($response->json() ?: [])
            ->map(fn (array $item) => [
                'raw' => $item,
                'normalized' => $this->normalizeGeoResult($item),
            ])
            ->filter(fn (array $item) => $this->matchesSearchQuery($query, $item['raw'], $item['normalized']))
            ->map(fn (array $item) => $item['normalized'])
            ->filter(fn (array $item) => $item['lat'] !== 0.0 && $item['lng'] !== 0.0 && $item['label'] !== '')
            ->values()
            ->all();

        $mergedResults = $this->mergeDirectoryResults($localResults, $remoteResults, $limit);

        return [
            'data' => $mergedResults,
            'meta' => $this->buildGeocodeMeta($query, $localResults, $remoteResults, $mergedResults),
        ];
    }

    public function geocode(string $query, int $limit = 5): array
    {
        return $this->geocodeSearch($query, $limit)['data'];
    }

    public function reverse(float $lat, float $lng): array
    {
        $response = Http::timeout(12)
            ->withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'fr',
                'User-Agent' => 'Kende Transport/1.0',
            ])
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'zoom' => 18,
                'addressdetails' => 1,
            ]);

        if (! $response->successful()) {
            return [
                'lat' => $lat,
                'lng' => $lng,
                'label' => sprintf('%.6f, %.6f', $lat, $lng),
                'address_line' => 'Position',
                'components' => $this->emptyAddressComponents(),
                'precision' => $this->buildPrecisionPayload([], 'fallback'),
            ];
        }

        $data = $response->json() ?: [];
        $normalized = $this->normalizeGeoResult(array_merge($data, [
            'lat' => $lat,
            'lon' => $lng,
        ]));

        return $normalized;
    }

    public function route(float $pickupLat, float $pickupLng, float $dropoffLat, float $dropoffLng): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Kende Transport/1.0',
            ])
            ->get(sprintf(
                'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s',
                $pickupLng,
                $pickupLat,
                $dropoffLng,
                $dropoffLat
            ), [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'false',
            ]);

        if ($response->successful()) {
            $route = $response->json('routes.0');

            if ($route && isset($route['geometry'])) {
                return [
                    'distance_km' => round(((float) ($route['distance'] ?? 0)) / 1000, 2),
                    'duration_minutes' => (int) ceil(((float) ($route['duration'] ?? 0)) / 60),
                    'geometry' => $route['geometry'],
                    'mode' => 'osrm',
                ];
            }
        }

        $distanceKm = $this->haversineKm($pickupLat, $pickupLng, $dropoffLat, $dropoffLng);
        $durationMinutes = (int) ceil(max(5, ($distanceKm / 28) * 60));

        return [
            'distance_km' => round($distanceKm, 2),
            'duration_minutes' => $durationMinutes,
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [$pickupLng, $pickupLat],
                    [$dropoffLng, $dropoffLat],
                ],
            ],
            'mode' => 'fallback',
        ];
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    protected function normalizeGeoResult(array $item): array
    {
        $lat = (float) ($item['lat'] ?? 0);
        $lng = (float) ($item['lon'] ?? 0);
        $address = is_array($item['address'] ?? null) ? $item['address'] : [];
        $addressLine = $this->buildAddressLine($item, $address);
        $label = $this->buildAddressLabel($addressLine, $address, $item['display_name'] ?? '');
        $components = $this->extractAddressComponents($address);
        $administrative = $this->congoAdministrativeHierarchyService->resolve([
            'country' => $components['country'] ?? null,
            'department' => $components['department'] ?? null,
            'state' => $components['state'] ?? null,
            'commune' => $components['commune'] ?? null,
            'city' => $components['city'] ?? null,
            'district' => $components['suburb'] ?? $components['city_district'] ?? $components['neighbourhood'] ?? null,
            'suburb' => $components['suburb'] ?? null,
            'city_district' => $components['city_district'] ?? null,
            'neighbourhood' => $components['neighbourhood'] ?? null,
        ]);

        return [
            'lat' => $lat,
            'lng' => $lng,
            'label' => $label !== '' ? $label : ($item['display_name'] ?? sprintf('%.6f, %.6f', $lat, $lng)),
            'address_line' => $addressLine !== '' ? $addressLine : ($item['name'] ?? ($item['display_name'] ?? 'Position')),
            'kind' => $this->detectResultKind($components, $item),
            'search_score' => null,
            'components' => $components,
            'administrative' => $administrative,
            'precision' => $this->buildPrecisionPayload($components, 'nominatim'),
        ];
    }

    protected function detectResultKind(array $components, array $item): string
    {
        if (! empty($components['road'])) {
            return 'road';
        }

        if (! empty($item['name']) || ! empty($item['address']['man_made'] ?? null)) {
            return 'landmark';
        }

        if (! empty($components['suburb']) || ! empty($components['city_district']) || ! empty($components['neighbourhood'])) {
            return 'district';
        }

        return 'area';
    }

    protected function extractAddressComponents(array $address): array
    {
        return [
            'house_number' => $this->sanitizeAddressPartForDisplay($address['house_number'] ?? null) ?: null,
            'road' => $this->sanitizeAddressPartForDisplay(
                $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? $address['path'] ?? null
            ) ?: null,
            'neighbourhood' => $this->sanitizeAddressPartForDisplay($address['neighbourhood'] ?? null) ?: null,
            'suburb' => $this->sanitizeAddressPartForDisplay($address['suburb'] ?? $address['quarter'] ?? null) ?: null,
            'city_district' => $this->sanitizeAddressPartForDisplay($address['city_district'] ?? $address['borough'] ?? null) ?: null,
            'city' => $this->sanitizeAddressPartForDisplay(
                $address['city'] ?? $address['town'] ?? $address['municipality'] ?? $address['village'] ?? null
            ) ?: null,
            'state' => $this->sanitizeAddressPartForDisplay($address['state'] ?? null) ?: null,
            'department' => $this->sanitizeAddressPartForDisplay($address['state'] ?? null) ?: null,
            'commune' => $this->sanitizeAddressPartForDisplay(
                $address['city'] ?? $address['town'] ?? $address['municipality'] ?? $address['village'] ?? null
            ) ?: null,
            'country' => $this->shortCountryLabel($address['country'] ?? null),
        ];
    }

    protected function emptyAddressComponents(): array
    {
        return [
            'house_number' => null,
            'road' => null,
            'neighbourhood' => null,
            'suburb' => null,
            'city_district' => null,
            'city' => null,
            'state' => null,
            'department' => null,
            'commune' => null,
            'country' => null,
        ];
    }

    protected function buildPrecisionPayload(array $components, string $source): array
    {
        return [
            'source' => $source,
            'level' => $this->detectPrecisionLevel($components),
            'house_number_confirmed' => ! empty($components['house_number']) && ! empty($components['road']),
            'road_confirmed' => ! empty($components['road']),
            'district_confirmed' => ! empty($components['suburb']) || ! empty($components['city_district']) || ! empty($components['neighbourhood']),
        ];
    }

    protected function detectPrecisionLevel(array $components): string
    {
        if (! empty($components['house_number']) && ! empty($components['road'])) {
            return 'door';
        }

        if (! empty($components['road'])) {
            return 'street';
        }

        if (! empty($components['suburb']) || ! empty($components['city_district']) || ! empty($components['neighbourhood'])) {
            return 'district';
        }

        return 'area';
    }

    protected function searchLocalDirectory(string $query, int $limit): array
    {
        $normalizedQuery = $this->normalizeAddressPart($query);
        if ($normalizedQuery === '') {
            return [];
        }

        if (! $this->shouldUseLocalDirectory($normalizedQuery)) {
            return [];
        }

        $entries = collect(config('kende_addresses.directory', []))
            ->map(function (array $entry) use ($normalizedQuery) {
                $score = $this->scoreLocalDirectoryEntry($entry, $normalizedQuery);

                return [
                    'score' => $score,
                    'entry' => $entry,
                ];
            })
            ->filter(fn (array $item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->take($limit)
            ->map(fn (array $item) => $this->normalizeLocalDirectoryEntry($item['entry']))
            ->all();

        return $entries;
    }

    protected function shouldUseLocalDirectory(string $normalizedQuery): bool
    {
        return ! in_array($normalizedQuery, [
            'brazzaville',
            'pointe noire',
            'pointenoire',
            'congo',
            'republique du congo',
            'republic of the congo',
        ], true);
    }

    protected function scoreLocalDirectoryEntry(array $entry, string $normalizedQuery): int
    {
        $queryTokens = $this->tokenizeQuery($normalizedQuery);
        $streetQuery = $this->parseStreetQuery($normalizedQuery);
        $candidates = array_merge(
            [
                $entry['address_line'] ?? '',
                data_get($entry, 'components.road', ''),
                data_get($entry, 'components.neighbourhood', ''),
                data_get($entry, 'components.suburb', ''),
                data_get($entry, 'components.city_district', ''),
            ],
            $entry['aliases'] ?? []
        );

        if ($streetQuery !== null && ! $this->localEntryMatchesStreetName($candidates, $streetQuery['street_name'])) {
            return 0;
        }

        $best = 0;

        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeAddressPart((string) $candidate);
            if ($normalizedCandidate === '') {
                continue;
            }

            if ($normalizedCandidate === $normalizedQuery) {
                $best = max($best, 120);
                continue;
            }

            if (str_contains($normalizedCandidate, $normalizedQuery)) {
                $best = max($best, 90);
                continue;
            }

            if (str_contains($normalizedQuery, $normalizedCandidate)) {
                $best = max($best, 75);
                continue;
            }

            similar_text($normalizedCandidate, $normalizedQuery, $percent);
            if ($percent >= 78) {
                $best = max($best, (int) round($percent));
            }

            $candidateTokens = $this->tokenizeQuery($normalizedCandidate);
            $overlap = count(array_intersect($queryTokens, $candidateTokens));
            if ($overlap > 0) {
                $best = max($best, 40 + ($overlap * 12));
            }
        }

        return $best + $this->localEntryWeight($entry, $normalizedQuery, $queryTokens);
    }

    protected function localEntryMatchesStreetName(array $candidates, string $streetName): bool
    {
        foreach (array_slice($candidates, 0, 2) as $candidate) {
            $normalizedCandidate = $this->normalizeAddressPart((string) $candidate);
            if ($normalizedCandidate !== '' && str_contains($normalizedCandidate, $streetName)) {
                return true;
            }
        }

        foreach (array_slice($candidates, 5) as $candidate) {
            $normalizedCandidate = $this->normalizeAddressPart((string) $candidate);
            if ($normalizedCandidate !== '' && str_contains($normalizedCandidate, $streetName)) {
                return true;
            }
        }

        return false;
    }

    protected function localEntryWeight(array $entry, string $normalizedQuery, array $queryTokens): int
    {
        $weight = 0;
        $kind = (string) ($entry['kind'] ?? 'road');
        $popularity = (int) ($entry['popularity'] ?? 50);
        $wantsStreet = $this->parseStreetQuery($normalizedQuery) !== null;
        $wantsLandmark = $this->looksLikeLandmarkQuery($queryTokens);

        if ($kind === 'road') {
            $weight += $wantsStreet ? 25 : 10;
        } elseif ($kind === 'district') {
            $weight += $wantsStreet ? 0 : 18;
        } elseif ($kind === 'landmark') {
            $weight += $wantsLandmark ? 28 : 12;
        }

        return $weight + (int) round($popularity / 10);
    }

    protected function tokenizeQuery(string $normalizedValue): array
    {
        $tokens = preg_split('/\s+/u', trim($normalizedValue)) ?: [];

        return array_values(array_filter($tokens, fn (string $token) => mb_strlen($token) >= 2));
    }

    protected function looksLikeLandmarkQuery(array $tokens): bool
    {
        $landmarkHints = [
            'stade',
            'marche',
            'hopital',
            'chu',
            'ecole',
            'lycee',
            'gare',
            'camp',
        ];

        return count(array_intersect($tokens, $landmarkHints)) > 0;
    }

    protected function normalizeLocalDirectoryEntry(array $entry): array
    {
        $components = $entry['components'] ?? $this->emptyAddressComponents();
        $precision = $entry['precision'] ?? $this->buildPrecisionPayload($components, 'kende_local');
        $administrative = $this->congoAdministrativeHierarchyService->resolve([
            'country' => $components['country'] ?? null,
            'department' => $components['department'] ?? $components['state'] ?? null,
            'state' => $components['state'] ?? null,
            'commune' => $components['commune'] ?? $components['city'] ?? null,
            'city' => $components['city'] ?? null,
            'district' => $components['suburb'] ?? $components['city_district'] ?? $components['neighbourhood'] ?? null,
            'suburb' => $components['suburb'] ?? null,
            'city_district' => $components['city_district'] ?? null,
            'neighbourhood' => $components['neighbourhood'] ?? null,
        ]);

        return [
            'lat' => (float) ($entry['lat'] ?? 0),
            'lng' => (float) ($entry['lng'] ?? 0),
            'label' => (string) ($entry['label'] ?? ''),
            'address_line' => (string) ($entry['address_line'] ?? $entry['label'] ?? ''),
            'kind' => (string) ($entry['kind'] ?? 'road'),
            'search_score' => (int) ($entry['popularity'] ?? 0),
            'components' => [
                'house_number' => $components['house_number'] ?? null,
                'road' => $components['road'] ?? null,
                'neighbourhood' => $components['neighbourhood'] ?? null,
                'suburb' => $components['suburb'] ?? null,
                'city_district' => $components['city_district'] ?? null,
                'city' => $components['city'] ?? null,
                'state' => $components['state'] ?? null,
                'department' => $components['department'] ?? $components['state'] ?? null,
                'commune' => $components['commune'] ?? $components['city'] ?? null,
                'country' => $components['country'] ?? null,
            ],
            'administrative' => $administrative,
            'precision' => $precision,
        ];
    }

    protected function mergeDirectoryResults(array $localResults, array $remoteResults, int $limit): array
    {
        $merged = [];
        $seen = [];

        foreach (array_merge($localResults, $remoteResults) as $item) {
            $key = $this->normalizeAddressPart((string) ($item['label'] ?? ''))
                . '|'
                . round((float) ($item['lat'] ?? 0), 5)
                . '|'
                . round((float) ($item['lng'] ?? 0), 5);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $merged[] = $item;

            if (count($merged) >= $limit) {
                break;
            }
        }

        return $merged;
    }

    protected function buildGeocodeMeta(string $query, array $localResults, array $remoteResults, array $mergedResults): array
    {
        $streetQuery = $this->parseStreetQuery($query);
        $needsClarification = $streetQuery !== null && count($mergedResults) === 0;

        return [
            'query_type' => $streetQuery !== null ? 'street' : 'generic',
            'local_results_count' => count($localResults),
            'remote_results_count' => count($remoteResults),
            'needs_clarification' => $needsClarification,
            'clarification_suggestions' => $needsClarification
                ? $this->buildClarificationSuggestions($query, 3)
                : [],
        ];
    }

    protected function buildClarificationSuggestions(string $query, int $limit = 3): array
    {
        $normalizedQuery = $this->normalizeAddressPart($query);
        $streetQuery = $this->parseStreetQuery($normalizedQuery);
        $queryTokens = $this->tokenizeQuery($streetQuery['street_name'] ?? $normalizedQuery);

        if ($queryTokens === []) {
            return [];
        }

        $hints = collect(config('kende_addresses.directory', []))
            ->flatMap(function (array $entry) {
                $components = $entry['components'] ?? [];
                $base = [
                    'lat' => (float) ($entry['lat'] ?? 0),
                    'lng' => (float) ($entry['lng'] ?? 0),
                    'components' => [
                        'house_number' => null,
                        'road' => null,
                        'neighbourhood' => null,
                        'suburb' => $components['suburb'] ?? null,
                        'city_district' => $components['city_district'] ?? null,
                        'city' => $components['city'] ?? 'Brazzaville',
                        'state' => $components['state'] ?? 'Brazzaville',
                        'department' => $components['department'] ?? $components['state'] ?? 'Brazzaville',
                        'commune' => $components['commune'] ?? $components['city'] ?? 'Brazzaville',
                        'country' => $components['country'] ?? 'Congo',
                    ],
                    'precision' => [
                        'source' => 'kende_local_hint',
                        'level' => 'district',
                        'house_number_confirmed' => false,
                        'road_confirmed' => false,
                        'district_confirmed' => true,
                    ],
                    'search_score' => (int) ($entry['popularity'] ?? 0),
                ];

                $hints = [];

                if (! empty($components['neighbourhood'])) {
                    $hints[] = $base + [
                        'kind' => 'neighbourhood',
                        'label' => trim($components['neighbourhood'] . ', ' . ($components['suburb'] ?? $components['city_district'] ?? 'Brazzaville') . ', ' . ($components['city'] ?? 'Brazzaville') . ', ' . ($components['country'] ?? 'Congo')),
                        'address_line' => (string) $components['neighbourhood'],
                    ];
                }

                if (($entry['kind'] ?? null) === 'landmark') {
                    $hints[] = $base + [
                        'kind' => 'landmark',
                        'label' => (string) ($entry['label'] ?? ''),
                        'address_line' => (string) ($entry['address_line'] ?? ''),
                    ];
                }

                if (($entry['kind'] ?? null) === 'district') {
                    $hints[] = $base + [
                        'kind' => 'district',
                        'label' => (string) ($entry['label'] ?? ''),
                        'address_line' => (string) ($entry['address_line'] ?? ''),
                    ];
                }

                return $hints;
            })
            ->map(function (array $hint) use ($queryTokens) {
                $candidate = $this->normalizeAddressPart(($hint['label'] ?? '') . ' ' . ($hint['address_line'] ?? ''));
                $candidateTokens = $this->tokenizeQuery($candidate);
                $overlap = count(array_intersect($queryTokens, $candidateTokens));
                $hint['match_score'] = $overlap > 0 ? 45 + ($overlap * 15) + (int) round(($hint['search_score'] ?? 0) / 10) : 0;

                return $hint;
            })
            ->filter(fn (array $hint) => ($hint['match_score'] ?? 0) > 0)
            ->sortByDesc('match_score')
            ->unique(fn (array $hint) => $this->normalizeAddressPart((string) ($hint['label'] ?? '')))
            ->take($limit)
            ->values()
            ->map(function (array $hint) {
                unset($hint['match_score']);

                return $hint;
            })
            ->all();

        return $hints;
    }

    protected function matchesSearchQuery(string $query, array $raw, array $normalized): bool
    {
        $queryMeta = $this->parseStreetQuery($query);
        if ($queryMeta === null) {
            return true;
        }

        $road = (string) data_get($normalized, 'components.road', '');
        $addressLine = (string) ($normalized['address_line'] ?? '');
        $rawName = (string) ($raw['name'] ?? '');

        $candidates = [
            $this->normalizeAddressPart($road),
            $this->normalizeAddressPart($addressLine),
            $this->normalizeAddressPart($rawName),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (str_contains($candidate, $queryMeta['street_name'])) {
                if ($queryMeta['street_type'] === null || str_contains($candidate, $queryMeta['street_type'])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function parseStreetQuery(string $query): ?array
    {
        $normalized = $this->normalizeAddressPart($query);
        if ($normalized === '') {
            return null;
        }

        $streetTypes = ['rue', 'avenue', 'av', 'boulevard', 'bd', 'route', 'allee', 'impasse'];
        foreach ($streetTypes as $streetType) {
            if (preg_match('/^' . preg_quote($streetType, '/') . '\s+(.+)$/u', $normalized, $matches) === 1) {
                $streetName = trim((string) ($matches[1] ?? ''));

                if ($streetName === '') {
                    return null;
                }

                return [
                    'street_type' => $streetType === 'av' ? 'avenue' : ($streetType === 'bd' ? 'boulevard' : $streetType),
                    'street_name' => $streetName,
                ];
            }
        }

        return null;
    }

    protected function buildAddressLine(array $item, array $address): string
    {
        $road = trim(implode(' ', array_filter([
            $address['house_number'] ?? null,
            $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? $address['path'] ?? null,
        ])));

        foreach ([
            $road,
            $address['neighbourhood'] ?? null,
            $address['suburb'] ?? null,
            $address['quarter'] ?? null,
            $address['city_district'] ?? null,
            $address['borough'] ?? null,
            $item['name'] ?? null,
            $item['display_name'] ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Position';
    }

    protected function buildAddressLabel(string $addressLine, array $address, string $fallback): string
    {
        $parts = [];

        foreach ([
            $addressLine,
            $this->formatDistrictLabel($address['suburb'] ?? $address['quarter'] ?? $address['neighbourhood'] ?? null),
            $this->formatDistrictLabel($address['city_district'] ?? $address['borough'] ?? null),
            $address['city'] ?? $address['town'] ?? $address['municipality'] ?? $address['village'] ?? null,
            $this->shortCountryLabel($address['country'] ?? null),
        ] as $candidate) {
            $this->pushUniqueAddressPart($parts, $this->sanitizeAddressPartForDisplay($candidate));
        }

        $label = implode(', ', $parts);

        return $label !== '' ? $label : trim($fallback);
    }

    protected function formatDistrictLabel(?string $value): ?string
    {
        $value = $this->sanitizeAddressPartForDisplay($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/arrondissement/i', $value)) {
            return $value;
        }

        return $value;
    }

    protected function shortCountryLabel(?string $country): ?string
    {
        $country = $this->sanitizeAddressPartForDisplay($country);
        if ($country === '') {
            return null;
        }

        return match (mb_strtolower($country)) {
            'congo-brazzaville', 'république du congo', 'republique du congo', 'republic of the congo' => 'Congo',
            default => $country,
        };
    }

    protected function pushUniqueAddressPart(array &$parts, ?string $candidate): void
    {
        $candidate = $this->sanitizeAddressPartForDisplay($candidate);
        if ($candidate === '') {
            return;
        }

        $normalized = $this->normalizeAddressPart($candidate);
        foreach ($parts as $part) {
            if ($this->normalizeAddressPart($part) === $normalized) {
                return;
            }
        }

        $parts[] = $candidate;
    }

    protected function normalizeAddressPart(string $value): string
    {
        $value = mb_strtolower(Str::ascii(trim($value)));
        $value = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $value);
        $value = preg_replace('/[^[:alnum:]\s]/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string) $value);
    }

    protected function sanitizeAddressPartForDisplay(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        return trim((string) $value);
    }
}
