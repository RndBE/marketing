<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTerm extends Model
{
    protected $fillable = ['invoice_id', 'parent_id', 'urutan', 'judul', 'isi'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('urutan');
    }
}
