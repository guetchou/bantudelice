<?php

namespace App\Services;

use Illuminate\Support\Str;

class CongoAdministrativeHierarchyService
{
    public function resolve(array $input): array
    {
        $country = $this->normalizeCountry($input['country'] ?? null);
        $department = $this->canonicalDepartment($input['department'] ?? $input['state'] ?? null);
        $commune = $this->canonicalCommune($input['commune'] ?? $input['city'] ?? null);
        $district = $this->sanitize($input['district'] ?? $input['suburb'] ?? $input['city_district'] ?? $input['neighbourhood'] ?? null);

        if ($department === null && $commune !== null) {
            $department = (string) data_get(config('congo_administrative.communes'), $commune . '.department');
        }

        $districtCanonical = $this->canonicalDistrict($commune, $district);

        return [
            'country' => $country,
            'country_known' => $country === (string) config('congo_administrative.country', 'Congo'),
            'department' => $department,
            'department_known' => $department !== null,
            'commune' => $commune,
            'commune_known' => $commune !== null,
            'district' => $districtCanonical ?? $district,
            'district_known' => $districtCanonical !== null,
            'district_scope' => $commune !== null
                ? data_get(config('congo_administrative.communes'), $commune . '.district_scope')
                : null,
            'official_department_count' => (int) config('congo_administrative.official_department_count', 15),
        ];
    }

    protected function canonicalDepartment(?string $value): ?string
    {
        $value = $this->normalize($value);
        if ($value === '') {
            return null;
        }

        foreach ((array) config('congo_administrative.departments', []) as $name => $meta) {
            $aliases = array_merge([$name], (array) ($meta['aliases'] ?? []));
            foreach ($aliases as $alias) {
                if ($this->normalize($alias) === $value) {
                    return $name;
                }
            }
        }

        return null;
    }

    protected function canonicalCommune(?string $value): ?string
    {
        $value = $this->normalize($value);
        if ($value === '') {
            return null;
        }

        foreach ((array) config('congo_administrative.communes', []) as $name => $meta) {
            $aliases = array_merge([$name], (array) ($meta['aliases'] ?? []));
            foreach ($aliases as $alias) {
                if ($this->normalize($alias) === $value) {
                    return $name;
                }
            }
        }

        return null;
    }

    protected function canonicalDistrict(?string $commune, ?string $value): ?string
    {
        $value = $this->normalize($value);
        if ($commune === null || $value === '') {
            return null;
        }

        $districts = (array) data_get(config('congo_administrative.communes'), $commune . '.districts', []);

        foreach ($districts as $name => $meta) {
            $aliases = array_merge([$name], (array) ($meta['aliases'] ?? []));
            foreach ($aliases as $alias) {
                if ($this->normalize($alias) === $value) {
                    return $name;
                }
            }
        }

        return null;
    }

    protected function normalizeCountry(?string $value): ?string
    {
        $value = $this->normalize($value);
        if ($value === '') {
            return null;
        }

        return match ($value) {
            'congo', 'congo brazzaville', 'republique du congo', 'republic of the congo' => (string) config('congo_administrative.country', 'Congo'),
            default => $this->sanitize($value),
        };
    }

    protected function sanitize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? preg_replace('/\s+/u', ' ', $value) : null;
    }

    protected function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        return trim(mb_strtolower(Str::ascii((string) $value)));
    }
}
