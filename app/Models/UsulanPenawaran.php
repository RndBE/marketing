<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsulanPenawaran extends Model
{
    protected $table = 'usulan_penawaran';

    protected $fillable = [
        'judul',
        'pic_id',
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
