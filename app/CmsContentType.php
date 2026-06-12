<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CmsContentType extends Model
{
    protected $table = 'cms_content_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'supports_revisions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_revisions' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $type) {
            if (empty($type->slug) && !empty($type->name)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CmsContentField::class, 'content_type_id')->orderBy('sort_order')->orderBy('id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(CmsContent::class, 'content_type_id')->latest('id');
    }
}
