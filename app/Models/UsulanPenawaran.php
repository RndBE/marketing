<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsulanPenawaran extends Model
{
    protected $table = 'usulan_penawaran';

    protected $fillable = [
        'company_id',
        'judul',
        'pic_id',
        'prospect_id',
        'deskripsi',
        'nilai_estimasi',
        'created_by',
        'status',
        'tanggapan',
        'ditanggapi_oleh',
        'tanggal_ditanggapi',
        'tanggal_dibutuhkan',
        'penawaran_id',
    ];

    protected $casts = [
        'nilai_estimasi' => 'integer',
        'tanggal_ditanggapi' => 'datetime',
        'tanggal_dibutuhkan' => 'date',
    ];

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
            'usulan_penawaran_company_visibility',
            'usulan_penawaran_id',
            'company_id'
        )->withTimestamps();
    }

    public function scopeVisibleToCompany(Builder $query, ?int $companyId): Builder
    {
        if (!$companyId) {
            return $query;
        }

        return $query->where(function (Builder $nested) use ($companyId) {
            $nested->where($this->getTable() . '.company_id', $companyId)
                ->orWhereHas('sharedCompanies', fn(Builder $sharedQuery) => $sharedQuery->where('companies.id', $companyId));
        });
    }

    public function isVisibleToCompany(?int $companyId): bool
    {
        if (!$companyId) {
            return false;
        }

        if ((int) $this->company_id === (int) $companyId) {
            return true;
        }

        if ($this->relationLoaded('sharedCompanies')) {
            return $this->sharedCompanies->contains(fn($company) => (int) $company->id === (int) $companyId);
        }

        return $this->sharedCompanies()->where('companies.id', $companyId)->exists();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditanggapi_oleh');
    }

    public function penawaran(): BelongsTo
    {
        return $this->belongsTo(Penawaran::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(UsulanAttachment::class, 'usulan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UsulanItem::class, 'usulan_id')->orderBy('urutan');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'menunggu' => 'Menunggu Tanggapan',
            'ditanggapi' => 'Sudah Ditanggapi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'slate',
            'menunggu' => 'amber',
            'ditanggapi' => 'blue',
            'disetujui' => 'green',
            'ditolak' => 'red',
            default => 'slate',
        };
    }
}
