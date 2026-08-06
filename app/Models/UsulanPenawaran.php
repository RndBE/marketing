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
        'target_company_id',
        'judul',
        'jenis_transaksi',
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
        'penawaran_status',
        'penawaran_tanggapan',
        'signature_name',
        'signature_position',
        'signature_city',
        'signature_date',
        'signature_path',
    ];

    protected $casts = [
        'nilai_estimasi' => 'integer',
        'tanggal_ditanggapi' => 'datetime',
        'tanggal_dibutuhkan' => 'date',
        'signature_date' => 'date',
    ];

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Pic::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function targetCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'target_company_id');
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
        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $nested) use ($companyId) {
            $nested->where('company_id', $companyId)
                ->orWhere('target_company_id', $companyId)
                ->orWhereNull('target_company_id');
        });
    }

    public function isVisibleToCompany(?int $companyId): bool
    {
        if (! $companyId) {
            return false;
        }

        return $this->target_company_id === null
            || (int) $this->company_id === (int) $companyId
            || (int) $this->target_company_id === (int) $companyId;
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

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'usulan_id');
    }

    public function isRequesterCompany(?int $companyId): bool
    {
        return $companyId !== null && (int) $this->company_id === (int) $companyId;
    }

    public function isSupplierCompany(?int $companyId): bool
    {
        return $companyId !== null && (int) $this->target_company_id === (int) $companyId;
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
