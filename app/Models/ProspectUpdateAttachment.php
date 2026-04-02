<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectUpdateAttachment extends Model
{
    protected $fillable = [
        'prospect_update_id',
        'file_name',
        'file_path',
        'mime',
        'size',
    ];

    public function prospectUpdate(): BelongsTo
    {
        return $this->belongsTo(ProspectUpdate::class);
    }
}
