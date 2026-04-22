<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSignatureTemplate extends Model
{
    protected $fillable = ['company_id', 'template_name', 'nama', 'jabatan', 'kota', 'ttd_path'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
