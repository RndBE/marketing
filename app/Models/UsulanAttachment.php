<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsulanAttachment extends Model
{
    protected $table = 'usulan_attachments';

    protected $fillable = [
        'usulan_id',
        'nama_file',
        'path',
        'tipe',
    ];

    public function usulan(): BelongsTo
    {
        return $this->belongsTo(UsulanPenawaran::class, 'usulan_id');
    }
}
