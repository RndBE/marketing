<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pic extends Model
{
    protected $fillable = ['honorific', 'nama', 'jabatan', 'instansi', 'email', 'no_hp', 'alamat'];

    public function penawaran()
    {
        return $this->hasMany(Penawaran::class, 'id_pic');
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class);
    }
}
