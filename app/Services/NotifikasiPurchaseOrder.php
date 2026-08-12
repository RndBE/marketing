<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderTerm;
use App\Models\User;
use App\Notifications\PurchaseOrderNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Pengirim notifikasi tahap Purchase Order sampai termin dan pembayarannya.
 *
 * PO pelanggan luar dan PO lama tanpa perusahaan penjual sengaja dilewati:
 * lawan transaksinya tidak ada di dalam sistem, jadi tidak ada yang bisa
 * dikabari.
 */
class NotifikasiPurchaseOrder
{
    private const PERMISSION = 'view-purchase-order';

    /** PO diunggah pembeli: yang perlu tahu perusahaan penjual. */
    public function poDikirim(PurchaseOrder $po, ?User $aktor = null): void
    {
        $this->kirim(
            $po->supplier_company_id,
            $aktor,
            new PurchaseOrderNotification(
                'po_dikirim',
                $po,
                'Purchase Order masuk',
                sprintf(
                    '%s mengirim %s untuk "%s" senilai %s.',
                    $po->company?->name ?? 'Perusahaan pembeli',
                    $po->nomor_po ?: 'Purchase Order',
                    $po->judul,
                    static::rupiah($po->total)
                ),
                $po->company?->name
            )
        );
    }

    /** PO yang ditolak diperbaiki pembeli: penjual perlu memverifikasi ulang. */
    public function poDiperbarui(PurchaseOrder $po, ?User $aktor = null): void
    {
        $this->kirim(
            $po->supplier_company_id,
            $aktor,
            new PurchaseOrderNotification(
                'po_diperbarui',
                $po,
                'Purchase Order diperbarui',
                sprintf(
                    '%s memperbaiki %s untuk "%s" senilai %s dan mengirimkannya kembali untuk diverifikasi.',
                    $po->company?->name ?? 'Perusahaan pembeli',
                    $po->nomor_po ?: 'Purchase Order',
                    $po->judul,
                    static::rupiah($po->total)
                ),
                $po->company?->name
            )
        );
    }

    /** PO diverifikasi penjual: yang perlu tahu perusahaan pembeli. */
    public function poDiverifikasi(PurchaseOrder $po, string $keputusan, ?User $aktor = null): void
    {
        [$judul, $keterangan] = $keputusan === 'approved'
            ? ['Purchase Order disetujui', 'menyetujui']
            : ['Purchase Order ditolak', 'menolak'];

        $this->kirim(
            $po->company_id,
            $aktor,
            new PurchaseOrderNotification(
                'po_diverifikasi',
                $po,
                $judul,
                sprintf(
                    '%s %s %s untuk "%s".',
                    $po->supplierCompany?->name ?? 'Perusahaan penjual',
                    $keterangan,
                    $po->nomor_po ?: 'Purchase Order',
                    $po->judul
                ),
                $po->supplierCompany?->name
            )
        );
    }

    /** Invoice termin diterbitkan penjual: yang perlu tahu perusahaan pembeli. */
    public function invoiceDiterbitkan(PurchaseOrder $po, PurchaseOrderTerm $term, ?User $aktor = null): void
    {
        $this->kirim(
            $po->company_id,
            $aktor,
            new PurchaseOrderNotification(
                'invoice_diterbitkan',
                $po,
                'Invoice termin diterbitkan',
                sprintf(
                    '%s menerbitkan invoice termin ke-%d senilai %s, jatuh tempo %s.',
                    $po->supplierCompany?->name ?? 'Perusahaan penjual',
                    (int) $term->pembayaran_ke,
                    static::rupiah($term->nilai_tagihan),
                    $term->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-'
                ),
                $po->supplierCompany?->name
            )
        );
    }

    /** Pelunasan dicatat penjual: yang perlu tahu perusahaan pembeli. */
    public function pembayaranDicatat(PurchaseOrder $po, PurchaseOrderTerm $term, ?User $aktor = null): void
    {
        $this->kirim(
            $po->company_id,
            $aktor,
            new PurchaseOrderNotification(
                'pembayaran_dicatat',
                $po,
                'Pembayaran termin lunas',
                sprintf(
                    '%s mencatat pelunasan termin ke-%d senilai %s.',
                    $po->supplierCompany?->name ?? 'Perusahaan penjual',
                    (int) $term->pembayaran_ke,
                    static::rupiah($term->nilai_tagihan)
                ),
                $po->supplierCompany?->name
            )
        );
    }

    private function kirim(?int $companyId, ?User $aktor, PurchaseOrderNotification $notification): void
    {
        $penerima = PenerimaNotifikasi::diPerusahaan($companyId, self::PERMISSION, $aktor);

        if ($penerima->isEmpty()) {
            return;
        }

        Notification::send($penerima, $notification);
    }

    private static function rupiah($nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 0, ',', '.');
    }
}
