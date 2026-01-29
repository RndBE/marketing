<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlurPenawaran extends Model
{
    protected $table = 'alur_penawaran';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'nama',
        'berlaku_untuk',
        'status',
        'dibuat_oleh',
    ];

    public function langkah()
    {
        return $this->hasMany(LangkahAlurPenawaran::class, 'alur_penawaran_id');
    }
}
