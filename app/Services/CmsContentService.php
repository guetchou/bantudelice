<?php

namespace App\Services;

use App\CmsContent;
use App\CmsContentField;
use App\CmsContentFieldValue;
use App\CmsContentRevision;
use App\CmsContentStatusLog;
use App\CmsContentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CmsContentService
{
    public function validationRules(CmsContentType $type, bool $isUpdate = false): array
    {
        $rules = [
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191',
            'status' => 'required|in:draft,pending_review,published,archived',
            'excerpt' => 'nullable|string',
            'layout' => 'nullable|string|max:191',
            'seo_title' => 'nullable|string|max:191',
            'seo_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
            'revision_note' => 'nullable|string|max:500',
        ];

        foreach ($type->fields as $field) {
            $rules['fields.' . $field->id] = $this->fieldRule($field, $isUpdate);
        }

        return $rules;
    }

    public function create(CmsContentType $type, array $contentData, array $fieldValues, ?int $userId = null): CmsContent
    {
        return DB::transaction(function () use ($type, $contentData, $fieldValues, $userId) {
            $contentData['content_type_id'] = $type->id;
            $contentData['author_id'] = $userId;
            $contentData['editor_id'] = $userId;
            $contentData['published_at'] = $this->resolvePublishedAt($contentData, null);

            $content = CmsContent::create($contentData);
            $this->syncValues($content, $type, $fieldValues, false);
            $this->createRevision($content, $userId, 'Initial revision');
            $this->logStatusChange($content, null, $content->status, $userId, 'Creation du contenu');

            return $content;
        });
    }

    public function update(CmsContent $content, array $contentData, array $fieldValues, ?int $userId = null): CmsContent
    {
        return DB::transaction(function () use ($content, $contentData, $fieldValues, $userId) {
            $contentData['editor_id'] = $userId;

            $fromStatus = $content->status;
            $nextStatus = $contentData['status'] ?? $content->status;

            $contentData['published_at'] = $this->resolvePublishedAt($contentData, $content);

            $content->update($contentData);
            $this->syncValues($content, $content->contentType, $fieldValues, true);
            $this->createRevision($content, $userId, $contentData['revision_note'] ?? 'Manual update');

            if ($fromStatus !== $content->status) {
                $this->logStatusChange($content, $fromStatus, $content->status, $userId, $contentData['revision_note'] ?? 'Transition editoriale');
            }

            return $content->fresh(['contentType', 'values']);
        });
    }

    public function transition(CmsContent $content, string $toStatus, ?int $userId = null, ?string $note = null): CmsContent
    {
        return DB::transaction(function () use ($content, $toStatus, $userId, $note) {
            $fromStatus = $content->status;
            $allowedTransitions = [
                'draft' => ['pending_review', 'published', 'archived'],
                'pending_review' => ['draft', 'published', 'archived'],
                'published' => ['draft', 'archived'],
                'archived' => ['draft', 'published'],
            ];

            if (!in_array($toStatus, $allowedTransitions[$fromStatus] ?? [], true)) {
                throw new \InvalidArgumentException("Transition CMS invalide: {$fromStatus} -> {$toStatus}");
            }

            $content->update([
                'status' => $toStatus,
                'editor_id' => $userId,
                'published_at' => $toStatus === 'published' ? ($content->published_at ?: now()) : ($toStatus === 'draft' ? null : $content->published_at),
            ]);

            $this->createRevision($content, $userId, $note ?: "Transition {$fromStatus} -> {$toStatus}");
            $this->logStatusChange($content, $fromStatus, $toStatus, $userId, $note);

            return $content->fresh(['contentType', 'values', 'statusLogs']);
        });
    }

    public function destroy(CmsContent $content): void
    {
        DB::transaction(function () use ($content) {
            $content->loadMissing(['values.field']);

            $imagePaths = $content->values
                ->filter(function (CmsContentFieldValue $value) {
                    return optional($value->field)->field_type === 'image' && !empty($value->value);
                })
                ->pluck('value')
                ->unique()
                ->values();

            $content->delete();

            $imagePaths->each(function (string $relativePath) {
                $this->deleteStoredFile($relativePath);
            });
        });
    }

    public function createRevision(CmsContent $content, ?int $userId = null, ?string $note = null): CmsContentRevision
    {
        $nextRevision = ((int) $content->revisions()->max('revision_number')) + 1;
        $payload = [
            'title' => $content->title,
            'slug' => $content->slug,
            'status' => $content->status,
            'excerpt' => $content->excerpt,
            'layout' => $content->layout,
            'seo_title' => $content->seo_title,
            'seo_description' => $content->seo_description,
            'fields' => $content->values()->with('field:id,key')->get()->mapWithKeys(function ($value) {
                return [$value->field->key => $value->value];
            })->toArray(),
        ];

        return CmsContentRevision::create([
            'content_id' => $content->id,
            'revision_number' => $nextRevision,
            'payload' => $payload,
            'created_by' => $userId,
            'note' => $note,
        ]);
    }

    public function fieldValueMap(CmsContent $content): array
    {
        return $content->values()->get()->mapWithKeys(function ($value) {
            return [$value->content_field_id => $value->value];
        })->toArray();
    }

    private function logStatusChange(CmsContent $content, ?string $fromStatus, string $toStatus, ?int $userId = null, ?string $note = null): void
    {
        CmsContentStatusLog::create([
            'content_id' => $content->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $userId,
            'note' => $note,
        ]);
    }

    private function syncValues(CmsContent $content, CmsContentType $type, array $fieldValues, bool $preserveMissingImages = false): void
    {
        foreach ($type->fields as $field) {
            $value = $fieldValues[$field->id] ?? null;

            if (
                $preserveMissingImages
                && $field->field_type === 'image'
                && !array_key_exists($field->id, $fieldValues)
            ) {
                continue;
            }

            if ($value instanceof UploadedFile) {
                $value = $this->storeFile($value, $field->key);
            } elseif (is_array($value)) {
                $value = json_encode($value);
            } elseif ($field->field_type === 'boolean') {
                $value = $value ? '1' : '0';
            }

            CmsContentFieldValue::updateOrCreate(
                [
                    'content_id' => $content->id,
                    'content_field_id' => $field->id,
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }

    private function storeFile(UploadedFile $file, string $key): string
    {
        $destination = public_path('images/cms');
        $this->ensureWritableDirectory($destination);

        $filename = $key . '-' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($destination, $filename);

        return 'images/cms/' . $filename;
    }

    private function deleteStoredFile(string $relativePath): void
    {
        $cleanPath = ltrim($relativePath, '/');
        if ($cleanPath === '') {
            return;
        }

        $absolutePath = public_path($cleanPath);
        $cmsRoot = public_path('images/cms') . DIRECTORY_SEPARATOR;

        if (!str_starts_with($absolutePath, $cmsRoot)) {
            return;
        }

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function ensureWritableDirectory(string $destination): void
    {
        if (!is_dir($destination) && !@mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('Impossible de creer le dossier CMS: ' . $destination);
        }

        if (!is_writable($destination)) {
            throw new RuntimeException('Le dossier CMS n\'est pas accessible en ecriture: ' . $destination);
        }
    }

    private function fieldRule(CmsContentField $field, bool $isUpdate): string
    {
        $required = $field->is_required && !$isUpdate ? 'required' : 'nullable';

        return match ($field->field_type) {
            'textarea', 'richtext', 'text', 'url', 'date', 'datetime' => $required . '|string',
            'number' => $required . '|numeric',
            'boolean' => 'nullable|boolean',
            'image' => $required . '|image|mimes:jpg,jpeg,png,webp|max:4096',
            'json' => $required . '|string',
            default => $required . '|string',
        };
    }

    private function resolvePublishedAt(array $contentData, ?CmsContent $content = null)
    {
        $status = $contentData['status'] ?? $content?->status;
        $incomingPublishedAt = $contentData['published_at'] ?? null;

        if ($status !== 'published') {
            return $incomingPublishedAt ?: $content?->published_at;
        }

        if (!empty($incomingPublishedAt)) {
            return $incomingPublishedAt;
        }

        return $content?->published_at ?: now();
    }
}
