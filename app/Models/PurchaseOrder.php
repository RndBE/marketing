<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'nomor_po',
        'judul',
        'supplier_nama',
        'supplier_alamat',
        'tgl_po',
        'status',
        'total',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'tgl_po' => 'date',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
