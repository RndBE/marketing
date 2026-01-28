<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranCover extends Model
{
    protected $table = 'penawaran_cover';
    protected $fillable = [
        'penawaran_id',
        'judul_cover',
        'subjudul',
        'perusahaan_nama',
        'perusahaan_alamat',
        'perusahaan_email',
        'perusahaan_telp',
        'logo_path',
        'intro_text'
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }
}
