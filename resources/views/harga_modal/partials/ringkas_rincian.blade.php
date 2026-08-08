{{--
    Tiga angka tingkat atas jawaban /rincian.

    Disusun sebagai persamaan, bukan tiga kartu terpisah, supaya terlihat dari mana
    harga modal di tab berasal: total biaya bahan dibagi jumlah produksi.

    Dibutuhkan: $hasil
    Opsional  : $lengket -- menempel di atas saat isi modal digulir
--}}
@php
    $lengket = $lengket ?? false;

    $rupiah = fn ($nilai) => is_numeric($nilai) ? 'Rp ' . number_format((float) $nilai, 0, ',', '.') : null;
    $cacah = fn ($nilai) => is_numeric($nilai) ? number_format((float) $nilai, 0, ',', '.') : null;

    $total = $rupiah($hasil->ringkas['total_biaya_bahan'] ?? null);
    $jumlah = $cacah($hasil->ringkas['jml_produksi'] ?? null);
    $satuan = $rupiah($hasil->ringkas['harga_modal_satuan'] ?? null);
@endphp

@if($total !== null || $jumlah !== null || $satuan !== null)
    {{-- Di modal: menyatu dengan bilah judul dengan menembus padding badannya, dan
         menempel di atas saat digulir. Di halaman penuh: kartu biasa. --}}
    <div class="{{ $lengket
        ? 'sticky top-0 z-10 -mx-5 -mt-5 mb-4 border-b border-slate-200 bg-white px-5 py-4'
        : 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm' }}">
        <div class="flex flex-wrap items-end gap-x-3 gap-y-3">
            <div>
                <p class="text-xs font-medium text-slate-500">Total Biaya Bahan</p>
                <p class="mt-0.5 text-base font-bold tabular-nums text-slate-900">{{ $total ?? '-' }}</p>
            </div>

            <span class="pb-1 text-lg font-semibold text-slate-300">&divide;</span>

            <div>
                <p class="text-xs font-medium text-slate-500">Jumlah Produksi</p>
                <p class="mt-0.5 text-base font-bold tabular-nums text-slate-900">
                    {{ $jumlah ?? '-' }}<span class="ml-1 text-xs font-normal text-slate-500">unit</span>
                </p>
            </div>

            <span class="pb-1 text-lg font-semibold text-slate-300">=</span>

            <div class="rounded-xl bg-slate-900 px-3 py-1.5">
                <p class="text-xs font-medium text-slate-300">Harga Modal / Unit</p>
                <p class="mt-0.5 text-base font-bold tabular-nums text-white">{{ $satuan ?? '-' }}</p>
            </div>
        </div>

        <p class="mt-2 text-xs text-slate-400">
            Harga modal per unit di tab {{ $tipe->label() }} berasal dari pembagian ini.
        </p>
    </div>
@endif
