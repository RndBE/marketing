<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranAttachment extends Model
{
    protected $table = 'penawaran_attachments';
    protected $fillable = ['penawaran_id', 'urutan', 'judul', 'file_path', 'mime', 'size'];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }
}
