{{--
    Bilah pencarian dan saringan.

    Sengaja diletakkan sebagai saudara dari kontainer tabel, bukan di dalamnya.
    Tabelnya bergulir mendatar sendiri (overflow-x-auto); kalau bilah ini ikut
    masuk ke sana, ia akan tergeser dan terpotong begitu tabelnya digulir.

    Form GET biasa: penyaringan terjadi di server atas seluruh baris tab, lalu
    hasilnya dipenggal jadi halaman. Kalau disaring di browser sesudah dipenggal,
    pencariannya hanya menjangkau halaman yang sedang tampil.

    Dibutuhkan: $tab, $saringan, $hanyaTersedia, $sumberTersedia, $labelBaris,
                $halaman, $jumlahTersaring
--}}
<form method="GET" action="{{ route('harga-modal.index') }}"
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <input type="hidden" name="tab" value="{{ $tab->value }}">

    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">

        {{-- Pencarian dibiarkan melebar mengisi ruang yang ada. --}}
        <div class="min-w-0 flex-1">
            <label for="cari-harga-modal" class="mb-1.5 block text-xs font-semibold text-slate-600">
                Cari
            </label>
            <div class="relative">
                <i class="ri-search-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input id="cari-harga-modal" type="search" name="cari" value="{{ $saringan->cari }}"
                       placeholder="{{ $tab->berbasisUnit() ? 'Cari nama produk, kode produksi, serial, atau sumber...' : 'Cari nama bahan atau sumber...' }}"
                       autocomplete="off"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm transition focus:border-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
            </div>
        </div>

        @if($sumberTersedia !== [])
            <div class="w-full lg:w-56">
                <label for="sumber-harga-modal" class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Sumber
                </label>
                <select id="sumber-harga-modal" name="sumber"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                    <option value="">Semua sumber</option>
                    @foreach($sumberTersedia as $pilihan)
                        <option value="{{ $pilihan }}" @selected($saringan->sumber === $pilihan)>{{ $pilihan }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            {{-- Mati secara bawaan: stok produk jadi sedang nol untuk seluruh unit,
                 jadi menyalakannya diam-diam akan membuat tabnya tampak kosong. --}}
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 transition hover:border-slate-300">
                <input type="checkbox" name="hanya_tersedia" value="1" @checked($hanyaTersedia)
                       class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/20">
                <span class="whitespace-nowrap">Hanya yang stoknya tersedia</span>
            </label>

            @if(! $tab->berbasisUnit())
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 transition hover:border-slate-300">
                    <input type="checkbox" name="menyimpang" value="1" @checked($saringan->hanyaMenyimpang)
                           class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/20">
                    <span class="whitespace-nowrap">Hanya selisih &ge;20%</span>
                </label>
            @endif
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                Terapkan
            </button>

            @if($saringan->aktif() || $hanyaTersedia)
                <a href="{{ route('harga-modal.index', ['tab' => $tab->value]) }}"
                   class="whitespace-nowrap rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                    Bersihkan
                </a>
            @endif
        </div>
    </div>

    {{-- Disusun utuh di PHP: kalimat yang dipecah antar baris Blade ikut membawa
         baris baru ke dalam HTML, dan @endif yang menempel di belakang kata tidak
         dikenali Blade sebagai direktif. --}}
    @php
        $awal = $halaman->total() === 0 ? 0 : (int) $halaman->firstItem();
        $akhir = $halaman->total() === 0 ? 0 : (int) $halaman->lastItem();

        $keterangan = 'Menampilkan ' . number_format($awal, 0, ',', '.')
            . '-' . number_format($akhir, 0, ',', '.')
            . ' dari ' . number_format($halaman->total(), 0, ',', '.')
            . ' ' . $labelBaris
            . ($saringan->aktif() ? ' yang cocok dengan saringan' : '') . '.';
    @endphp
    <p class="mt-3 text-xs text-slate-500">{{ $keterangan }}</p>
</form>
