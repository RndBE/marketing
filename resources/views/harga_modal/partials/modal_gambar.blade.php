{{--
    Pratinjau gambar.

    Berdiri sendiri, bukan pembungkus tabel. Permintaannya datang lewat event
    window `pratinjau-gambar`, jadi tidak peduli di mana thumbnail-nya berada --
    termasuk di dalam komponen modal rincian.

    Nama keadaannya berakhiran Gambar supaya tidak pernah bertabrakan dengan
    komponen lain di halaman ini. Tabrakan seperti itu tidak memunculkan galat,
    hanya membuka jendela yang salah.
--}}
<div x-data="pratinjauGambar"
     x-on:pratinjau-gambar.window="terimaGambar($event.detail)"
     x-show="terbukaGambar" style="display: none;"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-label="Pratinjau gambar"
     x-on:keydown.escape.window="tutupGambar()">

    <div x-show="terbukaGambar" x-on:click="tutupGambar()"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-slate-950/70 backdrop-blur-[2px]"></div>

    <div x-show="terbukaGambar"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="relative flex max-h-full w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">

        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-3">
            <p class="min-w-0 truncate font-mono text-sm font-semibold text-slate-900" x-text="judulGambar"></p>

            <button type="button" x-on:click="tutupGambar()" aria-label="Tutup pratinjau"
                    class="-mr-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <div class="flex min-h-0 flex-1 items-center justify-center bg-slate-50 p-4">
            {{-- Tautan Drive disematkan lewat penampil Drive sendiri. Dipasang dengan
                 x-if, bukan x-show, supaya iframe-nya benar-benar tidak ada saat tidak
                 dipakai -- src kosong akan membuat browser memuat halaman ini sendiri
                 ke dalam bingkainya. --}}
            <template x-if="sematan">
                <iframe :src="sematan" :title="judulGambar"
                        class="h-[70vh] w-full rounded-lg border-0 bg-white"></iframe>
            </template>

            {{-- Bukan tautan Drive: gambar yang sudah dimuat browser yang diperbesar,
                 tanpa memaksa iframe ke host yang mungkin menolak disematkan. --}}
            <template x-if="! sematan && gambar">
                <img :src="gambar" :alt="judulGambar"
                     referrerpolicy="no-referrer"
                     x-on:error="gagalGambar = true"
                     x-show="! gagalGambar"
                     class="max-h-[70vh] w-auto max-w-full rounded-lg object-contain">
            </template>

            <div x-show="(! sematan && ! gambar) || gagalGambar" style="display: none;"
                 class="px-6 py-12 text-center">
                <p class="text-sm text-slate-500">
                    Gambar ini tidak bisa ditampilkan di sini. Berkasnya mungkin belum
                    dibagikan, atau tautannya bukan tautan Google Drive.
                </p>

                <a x-show="tautanGambar" :href="tautanGambar" target="_blank" rel="noopener noreferrer"
                   class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-slate-900 underline">
                    <i class="ri-external-link-line"></i>
                    Buka di tab baru
                </a>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-3">
            <a x-show="tautanGambar" :href="tautanGambar" target="_blank" rel="noopener noreferrer" style="display: none;"
               class="mr-auto inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 underline transition hover:text-slate-900">
                <i class="ri-external-link-line"></i>
                Buka di Google Drive
            </a>

            <button type="button" x-on:click="tutupGambar()"
                    class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                Tutup
            </button>
        </div>
    </div>
</div>
