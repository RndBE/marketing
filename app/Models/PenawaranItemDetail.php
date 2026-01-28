<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranItemDetail extends Model
{
    protected $table = 'penawaran_item_details';
    protected $fillable = [
        'penawaran_item_id',
        'product_detail_id',
        'urutan',
        'nama',
        'spesifikasi',
        'qty',
        'satuan',
        'harga',
        'subtotal'
    ];

    public function item()
    {
        return $this->belongsTo(PenawaranItem::class, 'penawaran_item_id');
    }

    public function productDetail()
    {
        return $this->belongsTo(ProductDetail::class, 'product_detail_id');
    }
}
