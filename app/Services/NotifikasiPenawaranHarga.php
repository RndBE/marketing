<?php

namespace App\Services;

use App\Models\User;
use App\Models\UsulanPenawaran;
use App\Notifications\PenawaranHargaNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Pengirim notifikasi alur Penawaran Harga.
 *
 * Alurnya bolak-balik antar perusahaan, jadi setiap perpindahan bola dikabari
 * ke perusahaan lawan: peminta -> penjual saat permintaan dikirim, penjual ->
 * peminta saat ditanggapi/penawaran dikirim, lalu balik lagi saat penawarannya
 * disetujui/ditolak/diminta revisi.
 */
class NotifikasiPenawaranHarga
{
    /** Permintaan harga dikirim: yang perlu tahu perusahaan tujuan. */
    public function permintaanDikirim(UsulanPenawaran $usulan, ?User $aktor = null): void
    {
        $this->kirim(
            $usulan->target_company_id,
            'respond-usulan',
            $aktor,
            new PenawaranHargaNotification(
                'permintaan_dikirim',
                $usulan,
                'Permintaan harga baru',
                sprintf(
                    '%s mengirim permintaan harga "%s".',
                    $usulan->company?->name ?? 'Perusahaan lain',
                    $usulan->judul
                ),
                $usulan->company?->name
            )
        );
    }

    /** Permintaan ditanggapi penjual: yang perlu tahu perusahaan peminta. */
    public function permintaanDitanggapi(UsulanPenawaran $usulan, ?User $aktor = null): void
    {
        $status = match ($usulan->status) {
            'disetujui' => 'disetujui',
            'ditolak' => 'ditolak',
            default => 'ditanggapi',
        };

        $this->kirim(
            $usulan->company_id,
            'view-usulan',
            $aktor,
            new PenawaranHargaNotification(
                'permintaan_ditanggapi',
                $usulan,
                'Permintaan harga '.$status,
                sprintf(
                    '%s %s permintaan harga "%s".',
                    $usulan->targetCompany?->name ?? 'Perusahaan tujuan',
                    $status,
                    $usulan->judul
                ),
                $usulan->targetCompany?->name
            )
        );
    }

    /** Dokumen penawaran dikirim penjual: yang perlu tahu perusahaan peminta. */
    public function penawaranDikirim(UsulanPenawaran $usulan, ?User $aktor = null): void
    {
        $this->kirim(
            $usulan->company_id,
            'view-usulan',
            $aktor,
            new PenawaranHargaNotification(
                'penawaran_dikirim',
                $usulan,
                'Penawaran harga diterima',
                sprintf(
                    '%s mengirim penawaran harga untuk "%s".',
                    $usulan->targetCompany?->name ?? 'Perusahaan tujuan',
                    $usulan->judul
                ),
                $usulan->targetCompany?->name
            )
        );
    }

    /** Penawaran ditanggapi peminta: yang perlu tahu perusahaan penjual. */
    public function penawaranDitanggapi(UsulanPenawaran $usulan, string $aksi, ?User $aktor = null): void
    {
        [$judul, $keterangan] = match ($aksi) {
            'accepted' => ['Penawaran disetujui', 'menyetujui penawaran harga'],
            'revision_requested' => ['Revisi penawaran diminta', 'meminta revisi penawaran harga'],
            'rejected' => ['Penawaran ditolak', 'menolak penawaran harga'],
            default => ['Penawaran ditanggapi', 'menanggapi penawaran harga'],
        };

        $this->kirim(
            $usulan->target_company_id,
            'respond-usulan',
            $aktor,
            new PenawaranHargaNotification(
                'penawaran_ditanggapi',
                $usulan,
                $judul,
                sprintf(
                    '%s %s "%s".',
                    $usulan->company?->name ?? 'Perusahaan peminta',
                    $keterangan,
                    $usulan->judul
                ),
                $usulan->company?->name
            )
        );
    }

    private function kirim(?int $companyId, string $permission, ?User $aktor, PenawaranHargaNotification $notification): void
    {
        $penerima = $this->penerima($companyId, $permission, $aktor);

        if ($penerima->isEmpty()) {
            return;
        }

        Notification::send($penerima, $notification);
    }

    /**
     * Yang dikabari hanya orang di perusahaan tujuan yang memang menangani modul
     * ini. Kalau ternyata tidak ada satu pun yang punya permission-nya, notifikasi
     * jangan sampai hilang -- jatuhkan ke seluruh user perusahaan itu.
     *
     * @return Collection<int, User>
     */
    private function penerima(?int $companyId, string $permission, ?User $aktor): Collection
    {
        if (! $companyId) {
            return collect();
        }

        $users = User::query()
            ->where('company_id', $companyId)
            ->with('roles')
            ->get();

        $berhak = $users->filter(
            fn (User $user) => $user->isSuperadmin() || $user->hasPermission($permission)
        );

        return ($berhak->isNotEmpty() ? $berhak : $users)
            // Pelaku aksinya sudah tahu apa yang baru saja dia lakukan.
            ->reject(fn (User $user) => $aktor && (int) $user->id === (int) $aktor->id)
            ->values();
    }
}
