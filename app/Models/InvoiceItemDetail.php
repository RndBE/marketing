<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function productDetail(): BelongsTo
    {
        return $this->belongsTo(ProductDetail::class);
    }
}
