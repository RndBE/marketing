<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteDetail extends Model
{
    protected $table = 'note_details';
    protected $fillable = ['additional_note_id', 'notes_list', 'date_created'];

    public function additionalNote(): BelongsTo
    {
        return $this->belongsTo(AdditionalNote::class, 'additional_note_id');
    }
}
