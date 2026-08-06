<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderTerm extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'pembayaran_ke',
        'tanggal_jatuh_tempo',
        'nilai_tagihan',
        'nomor_invoice',
        'tanggal_invoice',
        'invoice_path',
        'nomor_faktur',
        'faktur_path',
        'tanggal_bayar',
        'nilai_dibayar',
        'bukti_bayar_path',
        'jenis_pph',
        'nilai_pph',
        'bukti_potong_pph_path',
        'status',
        'payment_verification_status',
        'payment_verification_notes',
        'payment_verified_by',
        'payment_verified_at',
        'catatan',
    ];

    protected $casts = [
        'tanggal_jatuh_tempo' => 'date',
        'tanggal_invoice' => 'date',
        'tanggal_bayar' => 'date',
        'nilai_tagihan' => 'decimal:2',
        'nilai_dibayar' => 'decimal:2',
        'nilai_pph' => 'decimal:2',
        'payment_verified_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getNilaiPelunasanAttribute(): float
    {
        return (float) $this->nilai_dibayar + (float) $this->nilai_pph;
    }

    public function getSisaTagihanAttribute(): float
    {
        return max(0, (float) $this->nilai_tagihan - $this->nilai_pelunasan);
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /**
     * Pembayaran dicatat penjual sendiri, jadi tidak ada lagi langkah verifikasi:
     * begitu pelunasan menutup tagihan, terminnya langsung lunas.
     */
    public function calculateStatus(): string
    {
        $pelunasan = $this->nilai_pelunasan;

        if ($pelunasan > 0 && $pelunasan >= (float) $this->nilai_tagihan) {
            return 'paid';
        }

        $hasInvoice = filled($this->nomor_invoice) || filled($this->invoice_path);

        if ($hasInvoice && $this->tanggal_jatuh_tempo?->isPast()) {
            return 'overdue';
        }

        if ($pelunasan > 0) {
            return 'partially_paid';
        }

        return $hasInvoice ? 'invoiced' : 'draft';
    }

    public function syncStatus(): void
    {
        $status = $this->calculateStatus();

        if ($this->status !== $status) {
            $this->forceFill(['status' => $status])->save();
        }
    }
}
