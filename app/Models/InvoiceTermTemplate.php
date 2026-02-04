<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTermTemplate extends Model
{
    protected $fillable = ['template_name', 'terms'];

    protected $casts = [
        'terms' => 'array',
    ];
}
