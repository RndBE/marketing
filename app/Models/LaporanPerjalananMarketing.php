<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanPerjalananMarketing extends Model
{
    protected $table = 'laporan_perjalanan_marketing';

    protected $fillable = [
        'nomor_laporan',
        'tanggal_pertemuan',
        'waktu_pertemuan',
        'tempat_pertemuan',
        'instansi',
        'pihak_ditemui',
        'peserta_internal',
        'topik_pembahasan',
        'hasil_pertemuan',
        'rencana_tindak_lanjut',
        'target_tindak_lanjut',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_pertemuan' => 'date',
        'target_tindak_lanjut' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LaporanPerjalananMarketingAttachment::class, 'laporan_id')->orderByDesc('id');
    }
}
