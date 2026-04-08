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

    public function calcBaseUnitSubtotal(): int
    {
        $detailTotal = (int) $this->details->sum(fn($detail) => $detail->calcSubtotal());
        if ($detailTotal > 0) {
            return $detailTotal;
        }

        if ($this->relationLoaded('details') && $this->details->count() === 0) {
            return 0;
        }

        return max((int) ($this->subtotal ?? 0), 0);
    }

    public function calcUnitSubtotal(): int
    {
        return (int) round($this->calcBaseUnitSubtotal() * $this->resolvedMarkup());
    }

    public function calcBundleUnitSubtotal(): int
    {
        return $this->calcUnitSubtotal();
    }

    public function calcRawSubtotal(): int
    {
        return (int) round($this->calcUnitSubtotal() * $this->resolvedQty());
    }

    public function calcDiscountAmount(): int
    {
        if (!$this->discount_enabled) {
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
        return max(0, $raw - $this->calcDiscountAmount());
    }
}
