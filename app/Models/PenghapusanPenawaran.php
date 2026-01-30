<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenghapusanPenawaran extends Model
{
    protected $table = 'penghapusan_penawaran';
    protected $fillable = [
        'penawaran_id',
        'nomor_penghapusan',
        'tanggal_penghapusan',
        'metode',
        'alasan',
        'disetujui_oleh',
        'status',
        'dibuat_oleh',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function dibuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
