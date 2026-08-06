<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'company_id',
        'supplier_company_id',
        'usulan_id',
        'penawaran_id',
        'nomor_po',
        'judul',
        'supplier_nama',
        'supplier_alamat',
        'tgl_po',
        'status',
        'sumber',
        'pembeli_nama',
        'pembeli_alamat',
        'jenis_transaksi',
        'total',
        'catatan',
        'po_file_path',
        'user_id',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'tgl_po' => 'date',
        'total' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    /**
     * Pembagi untuk label "Pembayaran ke-N dari M".
     *
     * Mengikuti jumlah termin yang benar-benar terjadwal saat itu, jadi ikut menyusut
     * bila pelunasan lebih cepat dan ikut bertambah bila jadwalnya diperpanjang.
     */
    public function jumlahTerminLabel(): int
    {
        return $this->relationLoaded('terms') ? $this->terms->count() : $this->terms()->count();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function usulan(): BelongsTo
    {
        return $this->belongsTo(UsulanPenawaran::class, 'usulan_id');
    }

    public function penawaran(): BelongsTo
    {
        return $this->belongsTo(Penawaran::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * PO yang diterima dari pelanggan di luar sistem. Perusahaan pemilik data adalah
     * penjualnya, dan pembelinya hanya tercatat sebagai nama -- bukan perusahaan yang
     * bisa membuka dokumen ini.
     */
    public function isExternalCustomerOrder(): bool
    {
        return $this->sumber === 'pelanggan_luar';
    }

    public function isBuyerCompany(?int $companyId): bool
    {
        if ($this->isExternalCustomerOrder()) {
            return false;
        }

        return $companyId !== null && (int) $this->company_id === (int) $companyId;
    }

    public function isSellerCompany(?int $companyId): bool
    {
        if ($this->isExternalCustomerOrder()) {
            return $companyId !== null && (int) $this->company_id === (int) $companyId;
        }

        return $companyId !== null && (int) $this->supplier_company_id === (int) $companyId;
    }

    public function terms(): HasMany
    {
        return $this->hasMany(PurchaseOrderTerm::class)->orderBy('pembayaran_ke');
    }

    public function getTotalTerjadwalAttribute(): float
    {
        return (float) $this->terms->sum('nilai_tagihan');
    }

    /**
     * Pembayaran dicatat penjual sendiri, jadi tidak ada lagi saringan verifikasi.
     * Menyaringnya akan membuat rekap bertentangan dengan status termin: termin lama
     * yang sempat berstatus menunggu verifikasi tampil "Lunas" tapi nilainya tidak
     * ikut terhitung, sehingga sisa pembayaran tidak pernah nol.
     */
    public function getTotalPelunasanAttribute(): float
    {
        return (float) $this->terms->sum(fn (PurchaseOrderTerm $term) => $term->nilai_pelunasan);
    }

    public function getSisaBelumTerjadwalAttribute(): float
    {
        return max(0, (float) $this->total - $this->total_terjadwal);
    }

    public function getSisaPembayaranAttribute(): float
    {
        return max(0, (float) $this->total - $this->total_pelunasan);
    }
}
