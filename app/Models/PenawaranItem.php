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
        'markup',
        'discount_enabled',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'subtotal' => 'integer',
        'markup' => 'decimal:2',
        'discount_enabled' => 'boolean',
        'discount_value' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(PenawaranItemDetail::class, 'penawaran_item_id')->orderBy('urutan');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function resolvedQty(): float
    {
        $qty = (float) ($this->qty ?? 1);

        return $qty > 0 ? $qty : 1.0;
    }

    public function resolvedMarkup(): float
    {
        $markup = (float) ($this->markup ?? 1);

        return $markup > 0 ? $markup : 1.0;
    }

    public function calcBundleUnitSubtotal(): int
    {
        return (int) $this->details->sum(fn($detail) => $detail->calcSubtotal());
    }

    public function calcRawSubtotal(): int
    {
        if ($this->tipe === 'bundle') {
            return (int) round($this->calcBundleUnitSubtotal() * $this->resolvedQty() * $this->resolvedMarkup());
        }

        $detailTotal = (int) $this->details->sum(fn($detail) => $detail->calcSubtotal());
        if ($detailTotal > 0) {
            return (int) round($detailTotal * $this->resolvedMarkup());
        }

        return (int) ($this->subtotal ?? 0);
    }

    public function calcDiscountAmount(): int
    {
        if ($this->tipe !== 'bundle' || !$this->discount_enabled) {
            return 0;
        }

        $raw = $this->calcRawSubtotal();
        $value = (float) ($this->discount_value ?? 0);
        $discount = ($this->discount_type ?? 'percent') === 'percent'
            ? (int) round($raw * ($value / 100))
            : (int) round($value);

        return min($discount, $raw);
    }

    public function calcSubtotal(): int
    {
        $raw = $this->calcRawSubtotal();

        if ($this->tipe === 'bundle') {
            return max(0, $raw - $this->calcDiscountAmount());
        }

        return $raw;
    }
}
