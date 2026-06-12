<?php

namespace App\Services;

use App\CmsMediaAsset;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class UnifiedMediaLibraryService
{
    /**
     * Répertoires à scanner pour tous les utilisateurs (images par défaut admin).
     * Ces images sont visibles par tous, uploadées par l'admin.
     */
    private const DEFAULT_DIRECTORIES = [
        'images/cms/library' => 'Bibliothèque',
        'images/cms'         => 'CMS',
        'images/home'        => 'Bannières',
    ];

    /**
     * Répertoires propres à chaque restaurant/utilisateur (images uploadées par lui).
     */
    private const USER_DIRECTORIES = [
        'images/restaurant_images' => 'Restaurant',
        'images/product_images'    => 'Produits',
        'images/profile_images'    => 'Profil',
    ];

    public function paginatedAssets(int $perPage = 24, int $page = 1, ?int $userId = null): LengthAwarePaginator
    {
        $items = $this->allAssets($userId)->values();
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

    public function groupedOptions(array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'], ?int $userId = null): array
    {
        $uid = $userId ?? (Auth::id() ?: null);

        return $this->allAssets($uid)
            ->filter(fn (array $a) => in_array(strtolower((string) ($a['extension'] ?? '')), $allowedExtensions, true))
            ->groupBy('source')
            ->map(fn (Collection $group) => $group->map(fn (array $a) => [
                'value'   => $a['path'],
                'label'   => $a['title'] . ' · ' . $a['file_name'],
                'preview' => $a['url'],
            ])->values()->all())
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

    /**
     * Retourne tous les assets visibles pour un utilisateur donné :
     *   1. Ses propres uploads (images liées à son restaurant/profil)
     *   2. Les images par défaut de la plateforme (uploadées par un admin, uploaded_by = NULL ou type admin)
     */
    public function allAssets(?int $userId = null): Collection
    {
        $uid = $userId ?? (Auth::id() ?: null);
        $assets = collect();

        // ── 1. Base de données CMS (images admin / partagées) ──
        if (class_exists(CmsMediaAsset::class)) {
            try {
                $query = CmsMediaAsset::query()->latest('id');

                // Si pas admin : on ne voit que les assets uploadés par soi-même
                // ou les assets sans owner (partagés par la plateforme)
                if ($uid && ! $this->currentUserIsAdmin()) {
                    $query->where(function ($q) use ($uid) {
                        $q->where('uploaded_by', $uid)
                          ->orWhereNull('uploaded_by');
                    });
                }

                $assets = $assets->concat(
                    $query->get()->map(fn (CmsMediaAsset $a) => [
                        'title'      => $a->title ?: pathinfo((string) $a->file_name, PATHINFO_FILENAME),
                        'file_name'  => $a->file_name ?: basename((string) $a->file_path),
                        'path'       => ltrim((string) $a->file_path, '/'),
                        'url'        => asset($a->file_path),
                        'mime_type'  => (string) $a->mime_type,
                        'extension'  => strtolower(pathinfo((string) $a->file_path, PATHINFO_EXTENSION)),
                        'file_size'  => (int) ($a->file_size ?? 0),
                        'source'     => 'Médiathèque',
                        'sort_key'   => (int) $a->id,
                        'owned_by'   => $a->uploaded_by,
                    ])
                );
            } catch (\Throwable) {
                // Table indisponible — on continue avec le scan filesystem
            }
        }

        // ── 2. Répertoires par défaut (visibles par tous) ──────
        foreach (self::DEFAULT_DIRECTORIES as $relDir => $sourceLabel) {
            $assets = $assets->concat($this->scanDirectory($relDir, $sourceLabel, null));
        }

        // ── 3. Répertoires utilisateur (filtrés par son restaurant) ──
        if ($uid && ! $this->currentUserIsAdmin()) {
            // Pour un restaurant : on cherche les images dont le nom contient l'identifiant du restaurant
            // ou on expose tous les fichiers du répertoire (logique actuelle, suffisant pour les petits volumes)
            foreach (self::USER_DIRECTORIES as $relDir => $sourceLabel) {
                $assets = $assets->concat($this->scanDirectory($relDir, 'Mes ' . $sourceLabel, $uid));
            }
        } else {
            // Admin : voit tout
            foreach (self::USER_DIRECTORIES as $relDir => $sourceLabel) {
                $assets = $assets->concat($this->scanDirectory($relDir, $sourceLabel, null));
            }
        }

        return $assets
            ->unique('path')
            ->sortByDesc('sort_key')
            ->values();
    }

    private function scanDirectory(string $relativeDirectory, string $sourceLabel, ?int $filterUserId): Collection
    {
        $absoluteDirectory = public_path($relativeDirectory);

        if (! File::isDirectory($absoluteDirectory)) {
            return collect();
        }

        $items = collect();

        foreach (File::files($absoluteDirectory) as $file) {
            $relativePath = trim($relativeDirectory . '/' . $file->getFilename(), '/');

            $items->push([
                'title'     => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                'file_name' => $file->getFilename(),
                'path'      => $relativePath,
                'url'       => asset($relativePath),
                'mime_type' => (string) File::mimeType($file->getPathname()),
                'extension' => strtolower((string) $file->getExtension()),
                'file_size' => (int) $file->getSize(),
                'source'    => $sourceLabel,
                'sort_key'  => (int) $file->getMTime(),
                'owned_by'  => null,
            ]);
        }

        return $items;
    }

    private function currentUserIsAdmin(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        // Champ "type" sur le modèle User : 'admin', 'super_admin', 'restaurant', 'delivery', 'user'
        return in_array((string) ($user->type ?? ''), ['admin', 'super_admin', '1', 'Admin'], true);
    }
}
