<?php

namespace App\Services;

use App\Models\UsulanPenawaran;

/**
 * Posisi sebuah berkas di sepanjang alur dagang: Permintaan -> Penawaran ->
 * Purchase Order -> Termin & Invoice.
 *
 * Satu sumber kebenaran untuk halaman detail (empat kartu tahap) dan daftar
 * (bilah ringkas per baris), supaya keduanya tidak pernah bercerita beda.
 */
class TahapPenawaranHarga
{
    /**
     * @return array<int, array{judul: string, label: string, tone: string}>
     */
    public static function untuk(UsulanPenawaran $usulan): array
    {
        return [
            [
                'judul' => '1. Permintaan Harga',
                'label' => match ($usulan->status) {
                    'draft' => 'Draft, belum dikirim',
                    'menunggu' => 'Menunggu tanggapan penjual',
                    'ditanggapi', 'disetujui' => 'Sudah ditanggapi penjual',
                    'ditolak' => 'Ditolak penjual',
                    default => $usulan->status_label,
                },
                'tone' => match ($usulan->status) {
                    'draft', 'menunggu' => 'current',
                    'ditolak' => 'danger',
                    default => 'complete',
                },
            ],
            [
                'judul' => '2. Penawaran',
                'label' => match ($usulan->penawaran_status) {
                    'draft' => 'Draft penawaran',
                    'sent' => 'Menunggu keputusan pembeli',
                    'accepted' => 'Penawaran disetujui',
                    'revision_requested' => 'Revisi diminta',
                    'rejected' => 'Penawaran ditolak',
                    default => 'Belum dibuat',
                },
                'tone' => match ($usulan->penawaran_status) {
                    'accepted' => 'complete',
                    'sent' => 'current',
                    'draft', 'revision_requested' => 'warning',
                    'rejected' => 'danger',
                    default => 'pending',
                },
            ],
            [
                'judul' => '3. Purchase Order',
                'label' => match ($usulan->purchaseOrder?->status) {
                    'submitted' => 'Menunggu verifikasi penjual',
                    'approved' => 'Disetujui penjual',
                    'rejected' => 'Ditolak penjual',
                    'cancelled' => 'Dibatalkan',
                    null => 'Belum diunggah',
                    default => ucfirst($usulan->purchaseOrder->status),
                },
                'tone' => match ($usulan->purchaseOrder?->status) {
                    'approved' => 'complete',
                    'submitted' => 'current',
                    'rejected', 'cancelled' => 'danger',
                    default => 'pending',
                },
            ],
            [
                'judul' => '4. Termin & Invoice',
                'label' => $usulan->purchaseOrder?->status === 'approved'
                    ? 'Termin aktif'
                    : 'Menunggu PO disetujui',
                'tone' => $usulan->purchaseOrder?->status === 'approved' ? 'current' : 'pending',
            ],
        ];
    }

    /**
     * Tahap yang sedang berjalan: yang pertama belum tuntas. Dipakai daftar
     * untuk merangkum posisi jadi satu baris.
     *
     * @return array{nomor: int, total: int, judul: string, label: string, tone: string}
     */
    public static function ringkas(UsulanPenawaran $usulan): array
    {
        $tahap = static::untuk($usulan);
        $total = count($tahap);

        foreach ($tahap as $index => $item) {
            if ($item['tone'] !== 'complete') {
                return [...$item, 'nomor' => $index + 1, 'total' => $total];
            }
        }

        return [...$tahap[$total - 1], 'nomor' => $total, 'total' => $total];
    }
}
