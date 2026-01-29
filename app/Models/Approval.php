<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    protected $fillable = [
        'status',
        'module',
        'ref_id',
        'current_step',
        'approved_by',
        'approved_at',
        'catatan'
        ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function penawarans(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'approval_id');
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class);
    }
}
