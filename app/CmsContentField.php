<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CmsContentField extends Model
{
    protected $table = 'cms_content_fields';

    protected $fillable = [
        'content_type_id',
        'name',
        'key',
        'field_type',
        'is_required',
        'sort_order',
        'default_value',
        'help_text',
        'options',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $field) {
            if (empty($field->key) && !empty($field->name)) {
                $field->key = Str::snake(Str::slug($field->name, '_'));
            }
        });
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(CmsContentType::class, 'content_type_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CmsContentFieldValue::class, 'content_field_id');
    }

    public function optionsList(): array
    {
        if (empty($this->options)) {
            return [];
        }

        $decoded = json_decode($this->options, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $this->options))));
    }
}
