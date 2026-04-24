<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospect extends Model
{
    protected $fillable = [
        'company_id',
        'tanggal_lead',
        'judul',
        'instansi',
        'pic_id',
        'nama_pic',
        'jabatan_pic',
        'no_hp',
        'email',
        'lokasi',
        'sumber_lead',
        'produk',
        'kebutuhan',
        'potensi_nilai',
        'status',
        'last_follow_up_at',
        'next_follow_up_at',
        'assigned_to',
        'catatan',
        'hasil_akhir',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_lead' => 'date',
        'last_follow_up_at' => 'date',
        'next_follow_up_at' => 'date',
        'potensi_nilai' => 'integer',
    ];

    public static function sourceOptions(): array
    {
        return [
            'Referral' => 'Referral',
            'Relasi pribadi' => 'Relasi pribadi',
            'Database lama' => 'Database lama',
            'Website' => 'Website',
            'WhatsApp' => 'WhatsApp',
            'Media sosial' => 'Media sosial',
            'Marketplace' => 'Marketplace',
            'Event' => 'Event',
            'Cold calling' => 'Cold calling',
            'Emailing' => 'Emailing',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'new' => 'Baru Lead',
            'qualified' => 'Qualified',
            'contacted' => 'Kontak Awal',
            'survey' => 'Survey / Meeting',
            'proposal_sent' => 'Proposal Sent',
            'negotiation' => 'Negosiasi',
            'waiting_decision' => 'Menunggu Keputusan',
            'won' => 'Menang',
            'lost' => 'Kalah',
            'on_hold' => 'On Hold',
        ];
    }

    public static function outcomeOptions(): array
    {
        return [
            'in_progress' => 'Masih Proses',
            'won' => 'Menang',
            'lost' => 'Kalah',
            'canceled' => 'Batal',
            'on_hold' => 'On Hold',
        ];
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Pic::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sharedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'prospect_company_visibility',
            'prospect_id',
            'company_id'
        )->withTimestamps();
    }

    public function scopeVisibleToCompany(Builder $query, ?int $companyId): Builder
    {
        // Prospek bersifat lintas perusahaan.
        return $query;
    }

    public function isVisibleToCompany(?int $companyId): bool
    {
        return (int) $companyId > 0;
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProspectUpdate::class)->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function penawarans(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'prospect_id')->orderByDesc('updated_at')->orderByDesc('id');
    }

    public function usulans(): HasMany
    {
        return $this->hasMany(UsulanPenawaran::class, 'prospect_id')->orderByDesc('updated_at')->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
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
        return static::outcomeOptions()[$this->hasil_akhir] ?? $this->hasil_akhir;
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

    public function getDisplayPicNameAttribute(): string
    {
        if (!empty($this->nama_pic)) {
            return $this->nama_pic;
        }

        if ($this->pic) {
            return trim(($this->pic->honorific ? $this->pic->honorific . ' ' : '') . $this->pic->nama);
        }

        return '-';
    }

    public function getDisplayInstansiAttribute(): string
    {
        return $this->instansi ?: ($this->pic?->instansi ?? '-');
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->judul ?: $this->display_instansi;
    }

    public function getDisplayPhoneAttribute(): string
    {
        return $this->no_hp ?: ($this->pic?->no_hp ?? '-');
    }

    public function getDisplayEmailAttribute(): string
    {
        return $this->email ?: ($this->pic?->email ?? '-');
    }
}
