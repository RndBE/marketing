# MCP Penawaran (Claude Code ↔ CRM)

Tim sales cukup menulis kebutuhannya ke Claude Code, misalnya *"buatkan penawaran
3 unit AWLR radar untuk BBWS Serayu Opak, pakai PPN 11%"*. Claude mencari
penawaran serupa, bundle, dan komponen di CRM, menyusun draft di chat, lalu
setelah disetujui membuat penawarannya langsung di CRM.

Endpoint: `https://crm.awass.site/mcp/penawaran` — server `App\Mcp\Servers\PenawaranServer`.

| Tool | Jenis | Isi |
|---|---|---|
| `cari-penawaran-serupa` | baca | penawaran lama + item, harga, diskon, status |
| `cari-bundle` | baca | bundle price list + rincian + harga |
| `cari-komponen` | baca | katalog komponen + harga |
| `cari-pic` | baca | kontak pelanggan (tanpa email/no. HP) |
| `buat-penawaran` | tulis | header + item + diskon/pajak dalam satu transaksi |

Semua tool berjalan sebagai pemilik token: nomor dokumen memakai kode SPH-nya,
penawaran masuk alur approval perusahaannya, dan `cari-penawaran-serupa` hanya
memperlihatkan penawaran miliknya sendiri kecuali ia admin atau punya izin
`view-all-penawaran`. Logika pembuatan dipakai bersama form web
(`App\Services\PenyusunPenawaran`), jadi hasilnya identik.

---

## Untuk sales

1. Minta token ke admin. Admin mengirim satu baris perintah `claude mcp add ...`.
2. Jalankan perintah itu sekali di terminal. Token itu rahasia — jangan ditempel ke chat atau grup.
3. Buka Claude Code di folder mana pun, lalu minta penawaran seperti biasa.
   Claude akan minta izin sebelum memanggil `buat-penawaran`.
4. Periksa hasilnya lewat link yang diberikan; perubahan berikutnya lewat web.

Butuh izin `create-penawaran`. Tanpa itu endpoint menolak (403).

## Untuk admin

```bash
php artisan mcp:token sales@contoh.com          # buat token, cetak perintah untuk sales
php artisan mcp:token sales@contoh.com --cabut  # cabut semua token MCP orang itu
```

Token tidak kedaluwarsa sendiri; cabut saat orangnya keluar atau laptopnya hilang.
Setiap panggilan MCP tercatat di Audit Log seperti request web lainnya.

## Deploy pertama (sekali)

Plesk hanya menyinkronkan source; `composer install` dan migrasi dijalankan manual.
Karena `User` memakai trait Sanctum, **paket harus terpasang sebelum kodenya tiba**,
atau semua halaman error sampai `composer install` selesai:

1. Deploy commit yang hanya mengubah `composer.json`/`composer.lock`, lalu di server
   `composer install --no-dev --optimize-autoloader`.
2. Deploy commit kodenya, lalu `php artisan migrate --force` (tabel `personal_access_tokens`).
   Kalau route di-cache, jalankan ulang `php artisan route:cache`.
3. Pastikan `APP_URL=https://crm.awass.site` (dipakai di perintah yang dicetak `mcp:token`)
   dan `APP_DEBUG=false` — dengan debug menyala, pesan galat internal ikut terkirim ke Claude.
