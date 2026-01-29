<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LangkahAlurPenawaran extends Model
{
    protected $table = 'langkah_alur_penawaran';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'alur_penawaran_id',
        'no_langkah',
        'nama_langkah',
        'user_id',
        'harus_semua',
        'kondisi',
    ];

    public function penawaran()
    {
        return $this->belongsTo(AlurPenawaran::class, 'alur_penawaran_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
