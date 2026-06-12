<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsContentStatusLog extends Model
{
    protected $table = 'cms_content_status_logs';

    protected $fillable = [
        'content_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(CmsContent::class, 'content_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
