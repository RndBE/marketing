<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komponen extends Model
{
    protected $table = 'komponen';

    protected $fillable = [
        'kode',
        'nama',
        'spesifikasi',
        'satuan',
        'harga',
        'is_active',
    ];

    protected $casts = [
        'harga' => 'integer',
        'is_active' => 'boolean',
    ];
}
