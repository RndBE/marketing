<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadReport extends Model
{
    protected $fillable = [
        'title',
        'original_filename',
        'file_path',
        'content',
        'uploaded_by',
        'company_id',
        'report_date',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
