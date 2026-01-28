<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocNumber extends Model
{
    protected $fillable = ['prefix', 'seq', 'doc_no'];

    public function penawarans(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'doc_number_id');
    }
}
