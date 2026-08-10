<?php

namespace App\Notifications;

use App\Models\UsulanPenawaran;
use Illuminate\Notifications\Notification;

/**
 * Satu kelas untuk seluruh alur Penawaran Harga, dua arah.
 *
 * Arah notifikasi ditentukan oleh kolom di recordnya (company_id vs
 * target_company_id), bukan oleh nama perusahaan, jadi CV -> PT dan PT -> CV
 * memakai jalur yang sama.
 */
class PenawaranHargaNotification extends Notification
{
    public function __construct(
        private string $jenis,
        private UsulanPenawaran $usulan,
        private string $judul,
        private string $pesan,
        private ?string $pengirim = null,
    ) {}

    /**
     * Sementara database saja. Kalau SMTP sudah siap, tambahkan 'mail' di sini
     * dan implement toMail() -- controller tidak perlu diubah sama sekali.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => $this->jenis,
            'usulan_id' => $this->usulan->id,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'pengirim' => $this->pengirim,
            'url' => route('penawaran-harga.show', $this->usulan->id),
        ];
    }
}
