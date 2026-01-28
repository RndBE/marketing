<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranSignature extends Model
{
    protected $table = 'penawaran_signatures';
    protected $fillable = ['penawaran_id', 'urutan', 'nama', 'jabatan', 'kota', 'tanggal', 'ttd_path'];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }
}
