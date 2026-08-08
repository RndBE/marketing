# Integrasi Harga Modal (CRM ↔ Inventory)

Halaman Harga Modal di CRM meminjam data HPP dari inventory. CRM tidak pernah
menyimpan angkanya; setiap kunjungan menanyakannya ulang.

Dokumen ini dua bagian: **langkah di sisi CRM** (dikerjakan tim CRM) dan
**yang dikirim ke tim inventory** (pertanyaan dan permintaan yang perlu dijawab).

---

## Bagian 1 — Langkah di sisi CRM

### 1.1 Konfigurasi server

Diisi di env server, bukan di repo. `CRM_API_KEY` diterima lewat jalur aman dan
tidak pernah ditempel ke chat, tiket, atau commit.

| Variabel | Isi | Catatan |
|---|---|---|
| `INVENTORY_BASE_URL` | alamat inventory | tanpa garis miring di ujung |
| `CRM_API_KEY` | kunci dari tim inventory | **jangan** diberi awalan `VITE_` — semua yang berawalan itu ikut terkirim ke browser |
| `INVENTORY_TIMEOUT` | `10` | detik |
| `HARGA_MODAL_CACHE_TTL` | `0` | 0 = tanpa cache; dipagari maksimum 300 detik |
| `HARGA_MODAL_CACHE_STORE` | kosong | kalau cache dinyalakan, arahkan ke `array`/`redis` |

Cache mati secara bawaan dengan sengaja: store cache aplikasi ini `database`,
jadi menyalakannya berarti baris HPP mendarat di tabel `cache` milik CRM.

### 1.2 Deploy

```
php artisan migrate      # membuat izin view-harga-modal
npm run build            # halaman ini memakai komponen Alpine baru
```

Migration `2026_08_07_090000_add_harga_modal_permission` hanya **membuat** izinnya,
tidak melekatkannya ke role mana pun. Siapa yang boleh melihat HPP ditentukan
sadar lewat halaman **Kelola Roles**, bukan sebagai efek samping deploy.

### 1.3 Beri izin

Kelola Roles → centang **Lihat Harga Modal** pada role yang berhak.

### 1.4 Uji dengan dua akun

| Akun | Harapan |
|---|---|
| Akun yang berhak (mis. Dewi) | Halaman terbuka, data tampil |
| Akun tanpa izin | **403** — inventory tidak ikut ditanya sama sekali |

Yang kedua sering terlewat padahal itu inti pengamanannya. Sudah dijaga test
otomatis, tapi tetap perlu dicoba manual sekali di server.

### 1.5 Perkakas diagnosa

Kalau ada kolom yang tampil `-` terus-menerus:

```
php artisan harga-modal:periksa <email> --tab=produk-jadi
php artisan harga-modal:periksa <email> --tab=produk-jadi --produksi-id=<id batch>
```

Keluarannya hanya **nama bidang dan tipenya**, tanpa nilai — aman disalin ke tiket.
Perintah ini memisahkan dua kemungkinan yang di layar terlihat sama: nama bidangnya
belum tercocokkan di CRM, atau inventory memang tidak mengirim bidang itu.

---

## Bagian 2 — Yang dikirim ke tim inventory

### 2.1 Yang diminta dikerjakan

1. **Terbitkan `CRM_API_KEY`** dan kirim lewat jalur aman (bukan email biasa/chat grup).
2. **Daftarkan email pengguna CRM** yang perlu akses. Email yang belum terdaftar
   dibalas `404`, dan CRM menampilkannya sebagai "Email Anda belum terdaftar di inventory".
3. **Beri hak harga modal per email**. Yang haknya kurang dibalas `403`, ditampilkan
   sebagai "Anda tidak punya akses harga modal".
4. **Whitelist IP server CRM**, kalau memang diberlakukan.
5. **Kirim `docs/api-crm-harga-modal.md`** kalau masih ada. Berkas itu belum pernah
   sampai, sehingga sebagian nama bidang di CRM masih hasil penyesuaian bertahap.

### 2.2 Yang dipanggil CRM

Semua panggilan **server-to-server**, dengan header `X-API-KEY`, timeout 10 detik.
Kunci tidak pernah menyentuh browser.

**Endpoint 1 — daftar per tab**

```
GET /api/crm/harga-modal?email=<email>&tab=<produk-jadi|setengah-jadi|bahan>
GET /api/crm/harga-modal?email=<email>&tab=<...>&hanya_tersedia=1
```

`hanya_tersedia` hanya dikirim kalau pengguna mencentangnya. Tidak dikirim sebagai
bawaan, karena stok produk jadi sedang nol untuk seluruh unit sehingga tabnya akan
tampak kosong.

**Endpoint 2 — rincian bahan satu batch**

```
GET /api/crm/harga-modal/rincian?email=<email>&tipe=<produk-jadi|setengah-jadi>&produksi_id=<id>
```

### 2.3 Bidang yang dibaca CRM saat ini

Tab **Produk Jadi** dan **Setengah Jadi** (kunci `produk_jadi`, `produk_setengah_jadi`):

| Kolom di layar | Bidang yang dibaca |
|---|---|
| Produk — gambar | `gambar_url` (src), `link_gambar` (tautan gambar penuh) |
| Produk — nama | `nama_produk` |
| Produk — serial | `serial_number` |
| Kode Produksi | `kode_produksi`, jatuh ke `kode_unit` kalau kosong |
| Stok Sisa | `stok_sisa` |
| Harga Modal / Unit | `harga_modal_satuan` |
| Sumber | `sumber` |
| tombol Lihat Bahan | `produksi_id` |

Tab **Bahan** (kunci `bahan`):

| Kolom di layar | Bidang yang dibaca |
|---|---|
| Gambar | `gambar_url` |
| Nama Bahan | `nama_bahan` |
| Stok Sisa | `stok_sisa` |
| Harga Beli Terakhir | `harga_modal_satuan` |
| Rata-rata Tertimbang | `harga_modal_rata2` |
| Nilai Persediaan | `nilai_persediaan` |
| Sumber | `sumber` |

Endpoint **/rincian** — bentuknya berbeda sendiri, bukan bentuk tab Bahan:

| Kolom di layar | Bidang yang dibaca |
|---|---|
| Gambar | `gambar_url` |
| Nama Bahan | `nama` |
| Kode | `kode` |
| Jenis | `jenis` |
| Batch | `batch` |
| Qty | `qty` |
| Harga Satuan | `harga_satuan` |
| Sub Total | `sub_total` |

Ditambah tiga angka tingkat atas yang ditampilkan di kepala modal sebagai
persamaan — `total_biaya_bahan` ÷ `jml_produksi` = `harga_modal_satuan` — supaya
terlihat dari mana harga modal di tab berasal.

Tidak ada `stok_sisa`, `harga_modal_rata2`, `nilai_persediaan`, maupun `sumber`
pada rincian, dan CRM tidak lagi memintanya: ketiadaannya memang wajar karena
isinya pemakaian bahan pada satu batch, bukan catatan persediaan gudang.

Tingkat atas untuk semua endpoint: `diambil_pada` (ditampilkan sebagai "data per …").

Nama samar seperti `harga`, `harga_satuan`, `hpp`, atau `unit_price` **sengaja
tidak diterima** sebagai pengganti harga modal. Di halaman HPP, salah mengambil
harga jual jauh lebih berbahaya daripada kolom yang kosong.

### 2.4 Pertanyaan yang perlu dijawab

**a. Bentuk baris rincian — sudah terjawab.** Bidangnya `nama`, `kode`, `qty`,
`harga_satuan`, `sub_total`, `jenis`, `gambar_url`, `batch`, ditambah
`jml_produksi`, `total_biaya_bahan`, dan `harga_modal_satuan` di tingkat atas.
CRM sudah memetakannya. Dicatat di sini karena sempat salah: komponen tabel tab
Bahan dipakai ulang untuk rincian, sehingga empat kolomnya tampil kosong.

**b. Nilai parameter `tipe`.** CRM mengirim `produk-jadi` / `setengah-jadi`,
mengikuti kosakata parameter `tab`. Mohon dikonfirmasi endpoint rincian memang
menerima nilai itu.

**c. Kunci pembungkus jawaban rincian.** CRM mencoba `rincian`, `baris`, `items`,
`data`, atau daftar langsung di akar. Yang mana yang dipakai?

**d. `gambar_url`.** URL penuh atau jalur relatif? CRM menerima keduanya — jalur
relatif disambung ke `INVENTORY_BASE_URL` — dan hanya meloloskan skema `http`/`https`.

**f. Content-Security-Policy.** Sudah diperiksa: aplikasi CRM **tidak memasang CSP
sama sekali** — tidak ada header maupun meta `http-equiv` di seluruh kode. Jadi
gambar Google Drive tidak akan terblokir dan tidak ada yang perlu diubah sekarang.
Kalau nanti CSP ditambahkan, `img-src` harus memuat **kedua** domain berikut,
karena URL thumbnail Drive melakukan redirect ke yang kedua:

```
img-src   'self' data: https://drive.google.com https://lh3.googleusercontent.com
frame-src https://drive.google.com
```

`frame-src` terpisah dari `img-src`, dan kelalaian itu sulit dilacak: thumbnail
tetap tampil normal sementara jendela pratinjaunya kosong, tanpa pesan galat di
mana pun. Keduanya harus diisi bersamaan.

Gambar unit diambil browser langsung dari Google, jadi tidak memakai API key dan
tidak terpengaruh apakah inventory terjangkau dari luar. Gambar di tab Bahan
berbeda: itu disajikan server inventory.

**e. Ringkasan.** CRM tidak lagi menampilkan blok ringkasan (jumlah unit, stok
tersedia, harga terakhir/terendah/tertinggi). Angkanya dihitung menyilang produk
yang tidak sebanding sehingga menyesatkan. Kalau endpoint masih mengirimnya, tidak
masalah — CRM mengabaikannya.

### 2.5 Perilaku status yang diandalkan CRM

Tiap status ditampilkan dengan pesan berbeda, jadi mohon dipertahankan artinya:

| Status | Ditampilkan sebagai |
|---|---|
| `200` | data |
| `401` | pesan teknis — kunci API CRM ditolak |
| `403` | "Anda tidak punya akses harga modal" |
| `404` | "Email Anda belum terdaftar di inventory" |
| `503` | pesan teknis — layanan sedang tidak tersedia |

Diratakan jadi satu pesan "error" akan menghilangkan bedanya: 403 berarti hak
kurang, 404 berarti data belum ada, 401/503 berarti yang rusak bukan penggunanya.

---

## Bagian 3 — Yang sengaja tidak dilakukan CRM

- **Tidak menyimpan HPP ke database CRM.** Tidak ada tabel, tidak ada kolom.
- **Tidak menaruh kunci API di browser.** Semua panggilan dari server; modal
  rincian pun memanggil route CRM sendiri, bukan inventory.
- **Tidak mencatat badan jawaban ke log.** Yang masuk log hanya status dan pesan
  pengecualian.
- **Tidak mengirim HPP lewat query string**, supaya tidak mengendap di riwayat
  browser dan log akses.
- **Tidak menebak nama bidang harga.** Kolom yang tidak tercocokkan tampil kosong
  disertai keterangan, bukan diisi angka terdekat yang kebetulan ada.
- **Tidak pernah menulis balik ke inventory.** Perhitungan margin dan harga jual
  seluruhnya di sisi CRM; tidak ada endpoint tulis yang dipanggil, dan harga jual
  tidak dikirim ke mana pun. Margin target disimpan di peramban masing-masing
  pengguna, bukan di server, sehingga setelan satu orang tidak mengubah tampilan
  orang lain.
