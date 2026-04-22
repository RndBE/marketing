<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTermTemplate extends Model
{
    protected $fillable = ['company_id', 'template_name', 'terms'];

    protected $casts = [
        'terms' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
