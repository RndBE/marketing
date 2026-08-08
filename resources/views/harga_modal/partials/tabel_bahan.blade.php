{{--
    Tabel untuk tab Bahan.

    Bentuknya beda dari tab berbasis unit: tidak ada kode produksi maupun serial,
    tapi ada tiga angka harga yang ketiganya ditampilkan. Harga terakhir dan
    rata-rata tertimbang kerap berjauhan, jadi memilih salah satu untuk mewakili
    keduanya akan menyesatkan.

    Nilai selisih_rata2 dan penanda menyimpang dihitung di HargaModalPayload, jadi
    tabel, saringan, dan peringatan di atasnya memakai satu definisi yang sama.

    Menerima satu halaman baris; penyaringan dan pemenggalannya sudah dilakukan di
    server (lihat SaringanHargaModal dan HargaModalController).

    Dibutuhkan: $baris
    Opsional  : $tampilkanHarga -- ketiga kolom harga disembunyikan kalau tidak satu
                pun baris membawa angkanya, supaya tabelnya tidak jadi deretan strip.
--}}
@php
    $rupiah = fn ($nilai) => is_numeric($nilai) ? 'Rp ' . number_format((float) $nilai, 0, ',', '.') : '-';
    $cacah = fn ($nilai) => is_numeric($nilai) ? number_format((float) $nilai, 0, ',', '.') : ($nilai ?? '-');

    $tampilkanHarga = $tampilkanHarga ?? true;
    $jumlahKolom = $tampilkanHarga ? 7 : 4;
@endphp

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th scope="col" class="px-5 py-3.5"><span class="sr-only">Gambar</span></th>
                <th scope="col" class="px-5 py-3.5">Nama Bahan</th>
                <th scope="col" class="px-5 py-3.5 text-right">Stok Sisa</th>

                @if($tampilkanHarga)
                    <th scope="col" class="px-5 py-3.5 text-right">
                        Harga Beli Terakhir
                        <span class="block text-[10px] font-normal normal-case tracking-normal text-slate-400">harga_modal_satuan</span>
                    </th>
                    <th scope="col" class="px-5 py-3.5 text-right">
                        Rata-rata Tertimbang
                        <span class="block text-[10px] font-normal normal-case tracking-normal text-slate-400">harga_modal_rata2</span>
                    </th>
                    <th scope="col" class="px-5 py-3.5 text-right">
                        Nilai Persediaan
                        <span class="block text-[10px] font-normal normal-case tracking-normal text-slate-400">nilai_persediaan</span>
                    </th>
                @endif

                <th scope="col" class="px-5 py-3.5">Sumber</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @forelse($baris as $item)
                <tr class="transition hover:bg-slate-50">
                    <td class="px-5 py-4">
                        {{-- Gambar bahan disajikan server inventory, bukan Google.
                             Komponennya tetap sama supaya aturan ukuran tetap,
                             placeholder, dan onerror hanya punya satu definisi. --}}
                        @include('harga_modal.partials.thumbnail', [
                            'url' => $item['gambar_url'] ?? null,
                            'alt' => 'Gambar ' . ($item['nama_produk'] ?? 'bahan'),
                            'judul' => $item['nama_produk'] ?? 'Gambar bahan',
                            'pratinjau' => true,
                        ])
                    </td>
                    <td class="px-5 py-4 font-medium text-slate-900">
                        {{ $item['nama_produk'] ?? '-' }}
                    </td>
                    <td class="px-5 py-4 text-right tabular-nums text-slate-700">
                        {{ $cacah($item['stok_sisa'] ?? null) }}
                    </td>
                    @if($tampilkanHarga)
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-slate-900">
                            {{ $rupiah($item['harga_modal'] ?? null) }}
                        </td>
                        <td class="px-5 py-4 text-right tabular-nums text-slate-900">
                            {{ $rupiah($item['harga_rata2'] ?? null) }}

                            @if($item['menyimpang'] ?? false)
                                {{-- Menyebut pembandingnya secara tegas: persentasenya
                                     dihitung terhadap rata-rata, bukan terhadap harga
                                     terakhir. Arahnya ikut supaya terlihat naik atau turun. --}}
                                @php
                                    $selisih = (float) $item['selisih_rata2'];
                                    $arah = $selisih > 0 ? 'di atas' : 'di bawah';
                                    $persen = number_format(abs($selisih) * 100, 0, ',', '.');
                                @endphp
                                <span class="mt-0.5 block text-xs font-medium text-amber-700">harga terakhir {{ $persen }}% {{ $arah }} rata-rata</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right tabular-nums text-slate-700">
                            {{ $rupiah($item['nilai_persediaan'] ?? null) }}
                        </td>
                    @endif

                    <td class="px-5 py-4 text-slate-600">
                        {{ $item['sumber'] ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $jumlahKolom }}" class="px-5 py-10 text-center text-sm text-slate-500">
                        Tidak ada bahan yang bisa ditampilkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
