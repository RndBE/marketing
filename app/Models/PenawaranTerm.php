<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranTerm extends Model
{
    protected $table = 'penawaran_terms';
    protected $fillable = ['penawaran_id', 'parent_id', 'urutan', 'judul', 'isi'];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('urutan')->orderBy('id');
    }
}
