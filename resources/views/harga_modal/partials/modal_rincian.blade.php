{{--
    Modal rincian bahan.

    Ditulis sendiri alih-alih memakai <x-modal> karena tabel bahan punya tujuh
    kolom, sementara komponen itu berhenti di sm:max-w-2xl.

    Badannya diambil lewat fetch ke route CRM `harga-modal.rincian`, bukan ke
    inventory. Kunci API tetap tinggal di server; yang dipanggil browser cuma
    halaman CRM sendiri, dengan sesi login yang sudah ada.

    Judulnya diisi dari baris yang diklik, jadi nama dan kode produk sudah terbaca
    sebelum datanya selesai dimuat.
--}}
{{-- style di bawah menahan kedipan sebelum Alpine sempat jalan. --}}
<div x-data="rincianBahan"
     x-on:rincian-bahan.window="terimaRincian($event.detail)"
     {{-- Pembungkus dilepas belakangan (terlihat), supaya transisi keluar
          anak-anaknya sempat berjalan sebelum semuanya disembunyikan. --}}
     x-show="terlihat" style="display: none;"
     class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
     role="dialog" aria-modal="true" aria-labelledby="judul-rincian-bahan"
     x-on:keydown.escape.window="tutup()">

    {{-- Latar. Klik di luar kartu menutup modal. --}}
    <div x-show="terbuka" x-on:click="tutup()"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-950/50 backdrop-blur-[1px]"></div>

    <div x-show="terbuka"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
         class="relative mx-auto w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-xl">

        {{-- Head: nama produk dan kode produksinya. --}}
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
                <h2 id="judul-rincian-bahan" class="truncate text-lg font-bold text-slate-900"
                    x-text="namaProduk || 'Rincian Bahan'"></h2>

                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-500">
                    <span class="font-mono text-slate-600" x-text="kodeProduk || produksiId"></span>
                    <span x-show="labelTipe" class="text-slate-300">&middot;</span>
                    <span x-show="labelTipe" x-text="labelTipe"></span>
                </p>

                {{-- Harga modal per unit. Angka ini datang dari baris produksinya,
                     bukan dari rincian bahan, jadi tetap ada meski rinciannya tidak
                     membawa harga apa pun. --}}
                <p x-show="hargaUnit" style="display: none;"
                   class="mt-2 inline-flex items-baseline gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5">
                    <span class="text-xs font-medium text-slate-500">Harga Modal / Unit</span>
                    <span class="text-sm font-bold tabular-nums text-slate-900" x-text="hargaUnit"></span>
                </p>
            </div>

            <button type="button" x-on:click="tutup()" aria-label="Tutup rincian"
                    class="-mr-1 -mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        {{-- Badan. Paginasi di dalamnya ikut dimuat ke modal, tidak memindahkan halaman. --}}
        <div class="max-h-[70vh] overflow-y-auto bg-slate-50 px-5 py-5">
            <div x-show="memuat" class="flex items-center justify-center gap-3 py-12 text-sm text-slate-500">
                <i class="ri-loader-4-line animate-spin text-lg"></i>
                Memuat rincian bahan...
            </div>

            <div x-show="galat" style="display: none;"
                 class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                <i class="ri-plug-line mt-0.5 text-lg text-rose-600"></i>
                <p x-text="galat"></p>
            </div>

            <div x-show="! memuat && ! galat" x-html="isi" x-on:click="lompat($event)"></div>
        </div>

        <div class="flex justify-end border-t border-slate-200 px-5 py-3">
            <a x-show="tautanPenuh" :href="tautanPenuh" style="display: none;"
               class="mr-auto text-xs font-medium text-slate-500 underline transition hover:text-slate-900">
                Buka sebagai halaman
            </a>

            <button type="button" x-on:click="tutup()"
                    class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                Tutup
            </button>
        </div>
    </div>
</div>
