<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranTermTemplate extends Model
{
    protected $table = 'penawaran_term_templates';
    protected $fillable = ['company_id', 'parent_id', 'urutan', 'judul', 'isi', 'is_active', 'group_name'];

    public function company()
    {
        return $this->belongsTo(Company::class);
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
