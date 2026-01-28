<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranValidity extends Model
{
    protected $table = 'penawaran_validity';
    protected $fillable = ['penawaran_id', 'mulai', 'sampai', 'berlaku_hari', 'keterangan'];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }
}
