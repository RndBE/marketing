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
        'subtotal',
        'markup',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga' => 'integer',
        'subtotal' => 'integer',
        'markup' => 'decimal:2',
    ];

    public function resolvedQty(): float
    {
        return max((float) ($this->qty ?? 0), 0);
    }

    public function resolvedMarkup(): float
    {
        $markup = (float) ($this->markup ?? 1);

        return $markup > 0 ? $markup : 1.0;
    }

    public function calcSubtotal(): int
    {
        $subtotal = (int) ($this->subtotal ?? 0);
        if ($subtotal > 0) {
            return $subtotal;
        }

        return (int) round($this->resolvedQty() * (int) ($this->harga ?? 0) * $this->resolvedMarkup());
    }

    public function item()
    {
        return $this->belongsTo(PenawaranItem::class, 'penawaran_item_id');
    }

    public function productDetail()
    {
        return $this->belongsTo(ProductDetail::class, 'product_detail_id');
    }
}
