<?php

namespace App\Services;

use App\CmsContent;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;

class CmsAccessService
{
    public function authorize(?User $user, string $ability, ?CmsContent $content = null): void
    {
        if (!$this->allows($user, $ability, $content)) {
            throw new AuthorizationException('Action CMS non autorisee.');
        }
    }

    public function allows(?User $user, string $ability, ?CmsContent $content = null): bool
    {
        if (!$user) {
            return false;
        }

        $type = strtolower((string) ($user->type ?? ''));
        $isPrivileged = in_array($type, ['admin', 'super_admin', 'super-admin', 'editor', 'editeur'], true);
        $isContributor = in_array($type, ['author', 'auteur', 'contributor', 'contributeur', 'editor', 'editeur'], true);
        $ownsContent = $content && ((int) $content->author_id === (int) $user->id || (int) $content->editor_id === (int) $user->id);

        return match ($ability) {
            'view', 'create' => $isPrivileged || $isContributor,
            'update', 'submit_review' => $isPrivileged || $ownsContent || $isContributor,
            'destroy' => $isPrivileged,
            'publish', 'archive' => $isPrivileged,
            'upload_media' => $isPrivileged || $isContributor,
            default => false,
        };
    }
}
