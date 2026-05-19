<?php

namespace App;

use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CmsContent extends Model
{
    protected $table = 'cms_contents';

    protected $fillable = [
        'content_type_id',
        'title',
        'slug',
        'status',
        'excerpt',
        'layout',
        'seo_title',
        'seo_description',
        'author_id',
        'editor_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $content) {
            if (empty($content->slug) && !empty($content->title)) {
                $content->slug = Str::slug($content->title);
            }
        });

        static::saved(function (self $content) {
            self::clearHomeCacheIfNeeded($content);
        });

        static::deleted(function (self $content) {
            self::clearHomeCacheIfNeeded($content);
        });
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(CmsContentType::class, 'content_type_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CmsContentFieldValue::class, 'content_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CmsContentRevision::class, 'content_id')->latest('revision_number');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(CmsContentStatusLog::class, 'content_id')->latest('id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    private static function clearHomeCacheIfNeeded(self $content): void
    {
        $content->loadMissing('contentType');

        if (optional($content->contentType)->slug !== 'home_section') {
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
