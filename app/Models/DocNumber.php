<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocNumber extends Model
{
    protected $fillable = ['company_id', 'prefix', 'doc_type', 'user_code', 'seq', 'month', 'year', 'doc_no'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function penawarans(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'doc_number_id');
    }
}
