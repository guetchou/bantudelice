<?php

namespace App\Services;

use App\CmsMediaAsset;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class UnifiedMediaLibraryService
{
    private const SCAN_DIRECTORIES = [
        'images/cms/library' => 'CMS',
        'images/cms' => 'CMS',
        'images/home' => 'Bannières home',
        'images/restaurant_images' => 'Restaurants',
        'images/product_images' => 'Produits',
        'images/profile_images' => 'Profils utilisateurs',
        'images/driver_images' => 'Livreurs et chauffeurs',
        'images/vehicle_images' => 'Véhicules',
    ];

    public function paginatedAssets(int $perPage = 24, int $page = 1): LengthAwarePaginator
    {
        $items = $this->allAssets()->values();
        $offset = max(0, ($page - 1) * $perPage);
        $slice = $items->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function groupedOptions(array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp']): array
    {
        return $this->allAssets()
            ->filter(function (array $asset) use ($allowedExtensions) {
                return in_array(strtolower((string) ($asset['extension'] ?? '')), $allowedExtensions, true);
            })
            ->groupBy('source')
            ->map(function (Collection $group) {
                return $group->map(function (array $asset) {
                    return [
                        'value' => $asset['path'],
                        'label' => $asset['title'] . ' · ' . $asset['file_name'],
                        'preview' => $asset['url'],
                    ];
                })->values()->all();
            })
            ->toArray();
    }

    public function copyToDirectory(string $sourceRelativePath, string $targetRelativeDirectory, ?string $prefix = null): string
    {
        $sourceRelativePath = ltrim(trim($sourceRelativePath), '/');

        if ($sourceRelativePath === '') {
            throw new RuntimeException('Chemin media vide.');
        }

        $sourceAbsolutePath = public_path($sourceRelativePath);

        if (! File::exists($sourceAbsolutePath)) {
            throw new RuntimeException('Media introuvable dans la mediatheque: ' . $sourceRelativePath);
        }

        $targetAbsoluteDirectory = public_path(trim($targetRelativeDirectory, '/'));

        if (! File::isDirectory($targetAbsoluteDirectory)) {
            File::makeDirectory($targetAbsoluteDirectory, 0775, true);
        }

        if (! is_writable($targetAbsoluteDirectory)) {
            throw new RuntimeException('Dossier cible non accessible en ecriture: ' . $targetRelativeDirectory);
        }

        $extension = strtolower((string) File::extension($sourceAbsolutePath));
        $baseName = $prefix ?: pathinfo($sourceAbsolutePath, PATHINFO_FILENAME);
        $filename = Str::slug($baseName) . '-' . uniqid() . ($extension ? '.' . $extension : '');

        File::copy($sourceAbsolutePath, $targetAbsoluteDirectory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    public function allAssets(): Collection
    {
        $assets = collect();

        if (class_exists(CmsMediaAsset::class) && File::exists(database_path(''))) {
            try {
                $assets = $assets->concat(
                    CmsMediaAsset::query()->latest('id')->get()->map(function (CmsMediaAsset $asset) {
                        return [
                            'title' => $asset->title ?: pathinfo((string) $asset->file_name, PATHINFO_FILENAME),
                            'file_name' => $asset->file_name ?: basename((string) $asset->file_path),
                            'path' => ltrim((string) $asset->file_path, '/'),
                            'url' => asset($asset->file_path),
                            'mime_type' => (string) $asset->mime_type,
                            'extension' => strtolower((string) pathinfo((string) $asset->file_path, PATHINFO_EXTENSION)),
                            'file_size' => (int) ($asset->file_size ?? 0),
                            'source' => 'CMS',
                            'sort_key' => (int) $asset->id,
                        ];
                    })
                );
            } catch (\Throwable $e) {
                // On garde la médiathèque fonctionnelle même si la table CMS est indisponible.
            }
        }

        foreach (self::SCAN_DIRECTORIES as $relativeDirectory => $sourceLabel) {
            $absoluteDirectory = public_path($relativeDirectory);

            if (! File::isDirectory($absoluteDirectory)) {
                continue;
            }

            foreach (File::files($absoluteDirectory) as $file) {
                $relativePath = trim($relativeDirectory . '/' . $file->getFilename(), '/');

                $assets->push([
                    'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'file_name' => $file->getFilename(),
                    'path' => $relativePath,
                    'url' => asset($relativePath),
                    'mime_type' => (string) File::mimeType($file->getPathname()),
                    'extension' => strtolower((string) $file->getExtension()),
                    'file_size' => (int) $file->getSize(),
                    'source' => $sourceLabel,
                    'sort_key' => (int) $file->getMTime(),
                ]);
            }
        }

        return $assets
            ->unique('path')
            ->sortByDesc('sort_key')
            ->values();
    }
}
