<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsulanItem extends Model
{
    protected $table = 'usulan_items';

    protected $fillable = [
        'usulan_id',
        'product_id',
        'tipe',
        'urutan',
        'judul',
        'catatan',
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

    public function usulan()
    {
        return $this->belongsTo(UsulanPenawaran::class, 'usulan_id');
    }
}
