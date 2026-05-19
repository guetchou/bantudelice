<?php

namespace App\Support;

trait HasDefaultAvatar
{
    public function avatarUrl()
    {
        if (!empty($this->image)) {
            $path = public_path('images/profile_images/' . $this->image);
            if (file_exists($path)) {
                return url('images/profile_images/' . $this->image);
            }
        }

        if (!empty($this->social_avatar)) {
            return $this->social_avatar;
        }

        return $this->defaultAvatarDataUri($this->resolveAvatarRole(), $this->name ?? 'Utilisateur');
    }

    protected function resolveAvatarRole()
    {
        if (!empty($this->type)) {
            return (string) $this->type;
        }

        return 'other';
    }

    protected function defaultAvatarDataUri($role, $name)
    {
        $palette = $this->defaultAvatarPalette($role);
        $initials = $this->avatarInitials($name);
        $label = $this->avatarLabel($role);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160" role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">' .
            '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1">' .
            '<stop offset="0%" stop-color="' . $palette['start'] . '" />' .
            '<stop offset="100%" stop-color="' . $palette['end'] . '" />' .
            '</linearGradient></defs>' .
            '<rect width="160" height="160" rx="36" fill="url(#g)" />' .
            '<circle cx="80" cy="62" r="28" fill="rgba(255,255,255,0.18)" />' .
            '<path d="M34 136c6-25 24-38 46-38s40 13 46 38" fill="rgba(255,255,255,0.18)" />' .
            '<text x="80" y="92" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="700" fill="#ffffff">' . $initials . '</text>' .
            '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    protected function defaultAvatarPalette($role)
    {
        $role = strtolower((string) $role);

        $palettes = [
            'admin' => ['start' => '#0f172a', 'end' => '#334155'],
            'restaurant' => ['start' => '#ea580c', 'end' => '#f59e0b'],
            'driver' => ['start' => '#0284c7', 'end' => '#0f766e'],
            'delivery' => ['start' => '#0284c7', 'end' => '#0f766e'],
            'courier' => ['start' => '#0284c7', 'end' => '#0f766e'],
            'user' => ['start' => '#059669', 'end' => '#10b981'],
            'other' => ['start' => '#6d28d9', 'end' => '#8b5cf6'],
        ];

        return $palettes[$role] ?? $palettes['other'];
    }

    protected function avatarLabel($role)
    {
        $labels = [
            'admin' => 'Avatar administrateur',
            'restaurant' => 'Avatar restaurant',
            'driver' => 'Avatar livreur',
            'delivery' => 'Avatar livreur',
            'courier' => 'Avatar coursier',
            'user' => 'Avatar utilisateur',
            'other' => 'Avatar',
        ];

        $role = strtolower((string) $role);
        return $labels[$role] ?? $labels['other'];
    }

    protected function avatarInitials($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'BD';
        }

        $parts = preg_split('/\s+/', $name);
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $letters .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($part, 0, 1)) : strtoupper(substr($part, 0, 1));
            if (strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'BD';
    }
}
