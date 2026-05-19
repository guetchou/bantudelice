<?php

namespace App;

use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsContentFieldValue extends Model
{
    protected $table = 'cms_content_field_values';

    protected $fillable = [
        'content_id',
        'content_field_id',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $value) {
            self::clearHomeCacheIfNeeded($value);
        });

        static::deleted(function (self $value) {
            self::clearHomeCacheIfNeeded($value);
        });
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(CmsContent::class, 'content_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CmsContentField::class, 'content_field_id');
    }

    private static function clearHomeCacheIfNeeded(self $value): void
    {
        $value->loadMissing(['content.contentType']);

        $content = $value->content;

        if (!$content || optional($content->contentType)->slug !== 'home_section') {
            return;
        }

        ConfigService::clearHomeContentCache(self::workspaceFromSlug((string) $content->slug));
    }

    private static function workspaceFromSlug(string $slug): string
    {
        return match (true) {
            str_starts_with($slug, 'kende-home-') => 'kende',
            str_starts_with($slug, 'mema-home-') => 'mema',
            default => 'bantudelice',
        };
    }
}
