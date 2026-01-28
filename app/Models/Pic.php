<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pic extends Model
{
    protected $fillable = ['nama', 'jabatan', 'instansi', 'email', 'no_hp', 'alamat'];

    public function penawaran()
    {
        return $this->hasMany(Penawaran::class, 'id_pic');
    }
}
