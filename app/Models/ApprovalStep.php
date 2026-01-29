<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $table = 'approval_step';

    protected $fillable = [
        'approval_id',
        'step_order',
        'step_name',
        'role_slug',
        'status',
        'approved_by',
        'approved_at',
        'catatan',
        'akses_approve'
    ];

    protected $casts = [
        'akses_approve' => 'array',
    ];

    public function approval()
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }
}
