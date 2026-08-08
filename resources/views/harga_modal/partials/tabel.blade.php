{{--
    Tabel harga modal untuk tab berbasis unit. Bentuk produk_jadi dan
    produk_setengah_jadi identik, jadi berkas ini dipakai apa adanya oleh keduanya.

    Baris dengan nama produk dan harga modal yang sama sudah dilebur jadi satu di
    HargaModalPayload. Yang dilebur selalu diberi keterangan supaya jelas bahwa
    ada beberapa unit di balik satu baris.

    Menerima satu halaman baris; penyaringan dan pemenggalannya sudah dilakukan di
    server (lihat SaringanHargaModal dan HargaModalController).

    Dibutuhkan: $baris
--}}
@php
    // Penanda "+N lainnya" untuk kolom yang isinya berbeda-beda di dalam satu baris leburan.
    $lain = fn (array $item, string $kolom) => (int) ($item[$kolom . '_lain'] ?? 0);
@endphp

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th scope="col" class="px-5 py-3.5">Foto Produk</th>
                {{-- Serial tetap menyatu dengan nama sebagai baris keterangannya,
                     jadi tidak ada kolom Serial yang berdiri sendiri. --}}
                <th scope="col" class="px-5 py-3.5">Nama Produk</th>
                <th scope="col" class="px-5 py-3.5">Kode Produksi</th>
                <th scope="col" class="px-5 py-3.5 text-right">Stok Sisa</th>
                <th scope="col" class="px-5 py-3.5 text-right">Harga Modal / Unit</th>
                <th scope="col" class="px-5 py-3.5 text-right">
                    Margin
                    <span class="block text-[10px] font-normal normal-case tracking-normal text-slate-400">bisa diubah per baris</span>
                </th>
                <th scope="col" class="px-5 py-3.5 text-right">Harga Jual</th>
                <th scope="col" class="px-5 py-3.5">Sumber</th>
                <th scope="col" class="px-5 py-3.5 text-right">Rincian</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @forelse($baris as $item)
                {{-- data-modal selalu ada, termasuk saat kosong, supaya urutan indeks
                     yang dibaca JavaScript tetap sejajar dengan yang ditulis Blade. --}}
                <tr class="transition hover:bg-slate-50"
                    data-modal="{{ is_numeric($item['harga_modal'] ?? null) ? $item['harga_modal'] : '' }}">
                    <td class="px-5 py-4">
                        @include('harga_modal.partials.thumbnail', [
                            'url' => $item['gambar_url'] ?? null,
                            'tautan' => $item['link_gambar'] ?? null,
                            'sematan' => $item['gambar_sematan'] ?? null,
                            'alt' => 'Gambar ' . ($item['nama_produk'] ?? 'produk'),
                            // Kode unit, bukan nama produk: satu nama bisa punya banyak
                            // unit, jadi kodenya yang menunjukkan foto mana.
                            'judul' => $item['kode_unit'] ?? $item['kode_produksi'] ?? ($item['nama_produk'] ?? 'Gambar produk'),
                            'pratinjau' => true,
                        ])
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-medium text-slate-900">{{ $item['nama_produk'] ?? '-' }}</p>

                        @if(($item['digabung'] ?? 1) > 1)
                            {{-- Tanpa angka harga, yang sama bukan harganya melainkan
                                 ketiadaannya -- jangan diklaim seolah sudah dibandingkan. --}}
                            @php
                                $keterangan = is_numeric($item['harga_modal'] ?? null)
                                    ? 'harga modal sama'
                                    : 'harga modal belum tersedia';
                            @endphp
                            <span class="mt-0.5 block text-xs font-normal text-slate-500">{{ $item['digabung'] }} unit, {{ $keterangan }}</span>
                        @endif

                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $item['serial'] ?? '-' }}</p>

                        @if($lain($item, 'serial') > 0)
                            <span class="block text-xs text-slate-400">
                                +{{ $lain($item, 'serial') }} serial lainnya
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 font-mono text-xs text-slate-600"
                        @if($item['kode_dari_unit'] ?? false) title="Kode produksi belum ada di sistem (data sebelum alur QC); yang ditampilkan kode unit." @endif>
                        {{ $item['kode_produksi'] ?? '-' }}

                        @if($lain($item, 'kode_produksi') > 0)
                            <span class="mt-0.5 block font-sans text-slate-400">
                                +{{ $lain($item, 'kode_produksi') }} kode lainnya
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right tabular-nums text-slate-700">
                        {{ is_numeric($item['stok_sisa'] ?? null) ? number_format((float) $item['stok_sisa'], 0, ',', '.') : ($item['stok_sisa'] ?? '-') }}
                    </td>
                    <td class="px-5 py-4 text-right font-semibold tabular-nums text-slate-900">
                        {{ is_numeric($item['harga_modal'] ?? null) ? 'Rp ' . number_format((float) $item['harga_modal'], 0, ',', '.') : ($item['harga_modal'] ?? '-') }}
                    </td>
                    @php
                        $modalBaris = is_numeric($item['harga_modal'] ?? null) ? (float) $item['harga_modal'] : null;
                        // Nilai awal dari server, supaya kolomnya sudah terisi benar
                        // sebelum JavaScript jalan. Alpine menimpanya saat init dengan
                        // margin tersimpan milik pengguna.
                        $jualAwal = \App\Services\Inventory\MarginHargaJual::hargaJual($modalBaris, $marginBawaan);
                    @endphp

                    {{-- Margin punya kolom sendiri supaya baris yang menyimpang dari
                         margin global bisa dipindai sekilas, tanpa membaca tiap sel. --}}
                    <td class="px-5 py-4">
                        @if($modalBaris === null)
                            <span class="block text-right text-slate-400">-</span>
                        @else
                            <div class="flex flex-col items-end gap-1 rounded-lg border-l-2 pl-2 transition"
                                 :class="diubah({{ $loop->index }}) ? 'border-amber-400' : 'border-transparent'">
                                <div class="flex items-center gap-1.5">
                                    <input type="text" inputmode="decimal"
                                           aria-label="Margin baris ini"
                                           value="{{ rtrim(rtrim(number_format($marginBawaan, 1, ',', '.'), '0'), ',') }}"
                                           :value="persenTampil(marginTarget({{ $loop->index }}))"
                                           x-on:change="setMarginBaris({{ $loop->index }}, $event.target.value)"
                                           class="w-16 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-right text-sm tabular-nums transition focus:border-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                                    <span class="text-xs text-slate-400">%</span>

                                    {{-- Penanda bahwa baris ini tidak lagi mengikuti margin global. --}}
                                    <button type="button" x-show="diubah({{ $loop->index }})" style="display: none;"
                                            x-on:click="kembalikan({{ $loop->index }})"
                                            title="Kembalikan ke margin global"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded-md text-amber-600 transition hover:bg-amber-50">
                                        <i class="ri-arrow-go-back-line text-sm"></i>
                                    </button>
                                </div>

                                <span x-show="diubah({{ $loop->index }})" style="display: none;"
                                      class="text-[11px] font-medium text-amber-600">diubah sendiri</span>
                            </div>
                        @endif
                    </td>

                    <td class="px-5 py-4">
                        @if($modalBaris === null)
                            <span class="block text-right text-slate-400">-</span>
                        @else
                            <div class="flex flex-col items-end gap-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-slate-400">Rp</span>
                                    <input type="text" inputmode="numeric"
                                           aria-label="Harga jual"
                                           value="{{ number_format((float) $jualAwal, 0, ',', '.') }}"
                                           :value="jualTampil({{ $loop->index }})"
                                           x-on:change="setJualBaris({{ $loop->index }}, $event.target.value)"
                                           class="w-32 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-right text-sm font-semibold tabular-nums text-slate-900 transition focus:border-slate-300 focus:ring-2 focus:ring-slate-900/10">
                                </div>

                                {{-- Muncul kalau pembulatan ke atas menggeser marginnya cukup
                                     jauh dari target -- pada barang murah itu bisa terasa. --}}
                                <span x-show="selisihPembulatan({{ $loop->index }}) !== null" style="display: none;"
                                      class="text-[11px] text-slate-400">
                                    margin efektif <span x-text="persenTampil(selisihPembulatan({{ $loop->index }}))"></span>%
                                </span>
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-slate-600">
                        {{ $item['sumber'] ?? '-' }}

                        @if($lain($item, 'sumber') > 0)
                            <span class="mt-0.5 block text-xs text-slate-400">
                                +{{ $lain($item, 'sumber') }} sumber lainnya
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        @php
                            $produksiId = $item['produksi_id'] ?? null;
                            $produksiLain = $lain($item, 'produksi_id');
                        @endphp

                        @if($produksiId === null)
                            {{-- Bukan kegagalan: batch produksinya memang tidak tercatat,
                                 jadi tidak ada rincian bahan yang bisa diminta. --}}
                            <button type="button" disabled
                                    title="Rincian bahan tidak ada di sistem untuk baris ini."
                                    class="cursor-not-allowed whitespace-nowrap rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-400">
                                Lihat Bahan
                            </button>
                        @else
                            {{-- Tautan sungguhan supaya Ctrl-klik tetap membuka halaman
                                 penuh; klik biasa dicegat Alpine dan dibuka di modal. --}}
                            <a href="{{ route('harga-modal.rincian', array_filter([
                                   'tipe' => $tab->value,
                                   'produksi_id' => $produksiId,
                                   'kode' => $item['kode_produksi'] ?? null,
                                   'nama' => $item['nama_produk'] ?? null,
                               ])) }}"
                               data-nama="{{ $item['nama_produk'] ?? '' }}"
                               data-kode="{{ $item['kode_produksi'] ?? '' }}"
                               data-produksi="{{ $produksiId }}"
                               data-tipe="{{ $tab->label() }}"
                               {{-- Harga modal per unit berasal dari baris produksinya, bukan dari
                                    rincian bahan. Diformat di sini supaya angkanya tidak dirakit
                                    ulang di JavaScript, dan tidak lewat URL supaya HPP tidak
                                    mengendap di riwayat browser atau log akses server. --}}
                               data-harga="{{ is_numeric($item['harga_modal'] ?? null) ? 'Rp ' . number_format((float) $item['harga_modal'], 0, ',', '.') : '' }}"
                               {{-- Penanda loading tautan global dimatikan: klik biasa membuka
                                    modal, bukan berpindah halaman, jadi tombolnya akan tertinggal
                                    dalam keadaan "Membuka..." selamanya. Modal punya penanda
                                    memuatnya sendiri. --}}
                               data-no-link-loading
                               x-on:click="window.bukaRincianBahan($event)"
                               class="inline-block whitespace-nowrap rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                                Lihat Bahan
                            </a>

                            @if($produksiLain > 0)
                                {{-- Baris ini leburan beberapa batch produksi. Rinciannya
                                     hanya mewakili batch pertama, dan itu perlu dikatakan. --}}
                                <span class="mt-1 block text-[11px] leading-tight text-slate-400">
                                    1 dari {{ $produksiLain + 1 }} produksi di baris ini
                                </span>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">
                        Tidak ada baris pada kategori ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
