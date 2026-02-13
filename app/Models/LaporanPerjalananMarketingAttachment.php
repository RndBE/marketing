<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPerjalananMarketingAttachment extends Model
{
    protected $table = 'laporan_perjalanan_marketing_attachments';

    protected $fillable = [
        'laporan_id',
        'file_name',
        'file_path',
        'mime',
        'size',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanPerjalananMarketing::class, 'laporan_id');
    }
}
