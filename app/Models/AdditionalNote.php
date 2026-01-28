<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalNote extends Model
{
    protected $table = 'additional_notes';
    protected $fillable = ['notes', 'has_detail', 'date_updated'];

    public function details(): HasMany
    {
        return $this->hasMany(NoteDetail::class, 'additional_note_id');
    }
}
