<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsMediaAsset extends Model
{
    protected $table = 'cms_media_assets';

    protected $fillable = [
        'title',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'alt_text',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
