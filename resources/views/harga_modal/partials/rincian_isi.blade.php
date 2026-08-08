{{--
    Isi rincian bahan satu batch produksi.

    Dipakai dua kali dengan sumber yang sama: sebagai badan modal (diambil lewat
    fetch ke route CRM) dan sebagai isi halaman penuh (cadangan kalau JavaScript
    mati atau tautannya dibuka di tab baru). Satu berkas supaya tidak ada dua
    versi yang bisa berbeda diam-diam.

    Dibutuhkan: $hasil, $halaman, $tipe
    Opsional  : $lengket
--}}
@if(! $hasil->berhasil)
    @include('harga_modal.partials.pesan_gagal')
@elseif($hasil->kosong())
    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center">
        <p class="text-sm text-slate-500">
            Inventory tidak mengembalikan bahan apa pun untuk batch produksi ini.
        </p>
    </div>
@else
    @php
        $keterangan = 'Menampilkan ' . number_format((int) $halaman->firstItem(), 0, ',', '.')
            . '-' . number_format((int) $halaman->lastItem(), 0, ',', '.')
            . ' dari ' . number_format($halaman->total(), 0, ',', '.') . ' bahan.';
    @endphp

    @include('harga_modal.partials.ringkas_rincian')

    <div class="space-y-4">
        <p class="text-xs text-slate-500">{{ $keterangan }}</p>

        @include('harga_modal.partials.tabel_rincian', ['baris' => $halaman->items()])

        @if($halaman->hasPages())
            <div>{{ $halaman->links() }}</div>
        @endif
    </div>
@endif
