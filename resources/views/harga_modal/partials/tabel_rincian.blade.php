{{--
    Tabel pemakaian bahan pada satu batch produksi.

    Berkas sendiri, bukan tabel tab Bahan yang dipakai ulang. Bentuk balikannya
    berbeda: di sini yang ada kuantitas pakai, harga satuan saat produksi, dan
    subtotalnya -- tidak ada stok sisa, rata-rata tertimbang, nilai persediaan,
    maupun sumber, dan ketiadaannya memang wajar.

    Dibutuhkan: $baris
--}}
@php
    $rupiah = fn ($nilai) => is_numeric($nilai) ? 'Rp ' . number_format((float) $nilai, 0, ',', '.') : '-';

    // Kuantitas bisa pecahan (mis. 2,5 meter). Angka bulat tetap tampil tanpa koma.
    $kuantitas = function ($nilai) {
        if (! is_numeric($nilai)) {
            return $nilai ?? '-';
        }

        $angka = (float) $nilai;

        return floor($angka) == $angka
            ? number_format($angka, 0, ',', '.')
            : rtrim(rtrim(number_format($angka, 2, ',', '.'), '0'), ',');
    };
@endphp

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th scope="col" class="px-5 py-3.5"><span class="sr-only">Gambar</span></th>
                <th scope="col" class="px-5 py-3.5">Nama Bahan</th>
                <th scope="col" class="px-5 py-3.5">Kode</th>
                <th scope="col" class="px-5 py-3.5">Jenis</th>
                <th scope="col" class="px-5 py-3.5">Batch</th>
                <th scope="col" class="px-5 py-3.5 text-right">Qty</th>
                <th scope="col" class="px-5 py-3.5 text-right">Harga Satuan</th>
                <th scope="col" class="px-5 py-3.5 text-right">Sub Total</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @forelse($baris as $item)
                <tr class="transition hover:bg-slate-50">
                    <td class="px-5 py-4">
                        @include('harga_modal.partials.thumbnail', [
                            'url' => $item['gambar_url'] ?? null,
                            'alt' => 'Gambar ' . ($item['nama'] ?? 'bahan'),
                        ])
                    </td>
                    <td class="px-5 py-4 font-medium text-slate-900">
                        {{ $item['nama'] ?? '-' }}
                    </td>
                    <td class="px-5 py-4 font-mono text-xs text-slate-600">
                        {{ $item['kode'] ?? '-' }}
                    </td>
                    <td class="px-5 py-4 text-slate-600">
                        {{ $item['jenis'] ?? '-' }}
                    </td>
                    <td class="px-5 py-4 font-mono text-xs text-slate-600">
                        {{ $item['batch'] ?? '-' }}
                    </td>
                    <td class="px-5 py-4 text-right tabular-nums text-slate-700">
                        {{ $kuantitas($item['qty'] ?? null) }}
                    </td>
                    <td class="px-5 py-4 text-right tabular-nums text-slate-700">
                        {{ $rupiah($item['harga_satuan'] ?? null) }}
                    </td>
                    <td class="px-5 py-4 text-right font-semibold tabular-nums text-slate-900">
                        {{ $rupiah($item['sub_total'] ?? null) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">
                        Tidak ada bahan yang bisa ditampilkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
