<?php

namespace App\Services;

use App\CmsMediaAsset;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CmsMediaService
{
    public function store(UploadedFile $file, ?int $userId = null, ?string $title = null, ?string $altText = null): CmsMediaAsset
    {
        $destination = public_path('images/cms/library');
        $this->ensureWritableDirectory($destination);

        $originalName = $file->getClientOriginalName();
        $originalExtension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        $filename = uniqid('cms-media-') . '.' . $originalExtension;
        $file->move($destination, $filename);

        return CmsMediaAsset::create([
            'title' => $title ?: pathinfo($originalName, PATHINFO_FILENAME),
            'file_path' => 'images/cms/library/' . $filename,
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'alt_text' => $altText,
            'uploaded_by' => $userId,
        ]);
    }

    private function ensureWritableDirectory(string $destination): void
    {
        if (!is_dir($destination) && !@mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('Impossible de creer le dossier media CMS: ' . $destination);
        }

        if (!is_writable($destination)) {
            throw new RuntimeException('Le dossier media CMS n\'est pas accessible en ecriture: ' . $destination);
        }
    }
}
