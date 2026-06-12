<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsContentRevision extends Model
{
    protected $table = 'cms_content_revisions';

    protected $fillable = [
        'content_id',
        'revision_number',
        'payload',
        'created_by',
        'note',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(CmsContent::class, 'content_id');
    }
}
