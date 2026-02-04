<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSignature extends Model
{
    protected $fillable = ['invoice_id', 'urutan', 'nama', 'jabatan', 'kota', 'tanggal', 'ttd_path'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
