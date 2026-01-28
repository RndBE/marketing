<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    protected $table = 'product_details';

    protected $fillable = [
        'product_id',
        'urutan',
        'nama',
        'spesifikasi',
        'qty',
        'satuan',
        'harga',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga' => 'integer',
        'subtotal' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
