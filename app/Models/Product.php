<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = ['kode', 'nama', 'deskripsi', 'satuan', 'is_active', 'foto'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(ProductDetail::class, 'product_id')->orderBy('urutan');
    }
}
