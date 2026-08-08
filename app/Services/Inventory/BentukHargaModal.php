<?php

namespace App\Services\Inventory;

/**
 * Bentuk baris yang dikirim inventory.
 *
 * Sengaja dipisah dari TabHargaModal. Tab menentukan apa yang diminta; bentuk
 * menentukan bagaimana jawabannya dibaca. Keduanya sempat disamakan, dan
 * akibatnya jawaban endpoint rincian dipetakan memakai bidang milik tab Bahan --
 * padahal isinya pemakaian bahan pada satu batch, bukan catatan persediaan.
 */
enum BentukHargaModal
{
    /** Unit hasil produksi: tab Produk Jadi dan Setengah Jadi. */
    case Unit;

    /** Catatan persediaan bahan: tab Bahan. */
    case Bahan;

    /** Pemakaian bahan pada satu batch produksi: endpoint /rincian. */
    case Rincian;
}
