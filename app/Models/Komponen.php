<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komponen extends Model
{
    protected $table = 'komponen';

    protected $fillable = [
        'company_id',
        'kode',
        'nama',
        'spesifikasi',
        'satuan',
        'harga',
        'is_active',
        'foto',
    ];

    protected $casts = [
        'harga' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
