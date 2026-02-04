<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranItem extends Model
{
    protected $table = 'penawaran_items';

    protected $fillable = [
        'penawaran_id',
        'product_id',
        'tipe',
        'urutan',
        'judul',
        'catatan',
        'qty',
        'satuan',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'subtotal' => 'integer',
    ];

    public function details()
    {
        return $this->hasMany(PenawaranItemDetail::class, 'penawaran_item_id')->orderBy('urutan');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
