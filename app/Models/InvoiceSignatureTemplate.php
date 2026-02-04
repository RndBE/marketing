<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSignatureTemplate extends Model
{
    protected $fillable = ['template_name', 'nama', 'jabatan', 'kota', 'ttd_path'];
}
