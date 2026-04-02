<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProspectUpdate extends Model
{
    protected $fillable = [
        'prospect_id',
        'tanggal',
        'aktivitas',
        'status',
        'next_follow_up_at',
        'hasil_akhir',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'next_follow_up_at' => 'date',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProspectUpdateAttachment::class)->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return Prospect::statusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'slate',
            'qualified' => 'emerald',
            'contacted' => 'sky',
            'survey' => 'indigo',
            'proposal_sent' => 'amber',
            'negotiation' => 'orange',
            'waiting_decision' => 'violet',
            'won' => 'green',
            'lost' => 'rose',
            'on_hold' => 'yellow',
            default => 'slate',
        };
    }

    public function getOutcomeLabelAttribute(): string
    {
        return Prospect::outcomeOptions()[$this->hasil_akhir] ?? $this->hasil_akhir;
    }

    public function getOutcomeColorAttribute(): string
    {
        return match ($this->hasil_akhir) {
            'won' => 'green',
            'lost' => 'rose',
            'canceled' => 'red',
            'on_hold' => 'yellow',
            default => 'blue',
        };
    }
}
