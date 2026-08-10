<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Notification;

/**
 * Tahap 3 dan 4 alur dagang: Purchase Order sampai termin dan pembayarannya.
 *
 * Sama seperti [PenawaranHargaNotification], arahnya ditentukan kolom di
 * recordnya -- company_id itu pembeli, supplier_company_id penjualnya.
 */
class PurchaseOrderNotification extends Notification
{
    public function __construct(
        private string $jenis,
        private PurchaseOrder $purchaseOrder,
        private string $judul,
        private string $pesan,
        private ?string $pengirim = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => $this->jenis,
            'purchase_order_id' => $this->purchaseOrder->id,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'pengirim' => $this->pengirim,
            'url' => route('purchase-orders.show', $this->purchaseOrder->id),
        ];
    }
}
