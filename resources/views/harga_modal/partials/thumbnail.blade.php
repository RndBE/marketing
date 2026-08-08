{{--
    Kotak gambar berukuran tetap.

    Ukurannya sama untuk ketiga keadaan -- ada gambar, belum ada, dan gagal dimuat --
    supaya tinggi baris tidak loncat-loncat mengikuti ada tidaknya foto.

    Placeholder selalu ada di belakang gambar. Kalau berkasnya belum dibagikan atau
    URL-nya menolak, onerror menyingkirkan <img> dan placeholder itu yang terlihat,
    bukan ikon gambar rusak.

    referrerpolicy="no-referrer" dipasang karena Google Drive kadang menolak
    permintaan gambar yang membawa referrer dari domain lain.

    Dibutuhkan: $url (boleh null)
    Opsional  : $tautan, $sematan, $alt, $judul, $ukuran, $pratinjau

    $alt menjelaskan gambarnya untuk pembaca layar, $judul dipakai sebagai kepala
    pratinjau. Dipisah karena "Gambar Panel Surya" enak dibaca sebagai alt, tapi
    janggal sebagai judul jendela.
--}}
@php
    $tautan = $tautan ?? null;
    $sematan = $sematan ?? null;
    $alt = $alt ?? 'Gambar';
    $judul = $judul ?? $alt;
    $ukuran = $ukuran ?? 'h-16 w-16';
    $pratinjau = $pratinjau ?? false;

    // Tetap <a> dengan href sungguhan, bukan <button>: dengan begitu ctrl+klik dan
    // klik-tengah tetap membuka gambar di tab baru. Klik biasa yang ditahan.
    // Kalau tidak ada link_gambar, gambarnya sendiri yang dituju.
    $sasaran = $tautan ?? $url;
    $dapatDiklik = $sasaran !== null && ($pratinjau || $tautan !== null);
@endphp

{{-- Pembungkus tautan sengaja dibuka dan ditutup di dalam @if terpisah supaya
     markup kotaknya tidak perlu ditulis dua kali. --}}
@if($dapatDiklik)
    <a href="{{ $sasaran }}" target="_blank" rel="noopener noreferrer"
       title="{{ $pratinjau ? 'Lihat gambar lebih besar' : 'Buka gambar penuh' }}"
       @if($pratinjau)
           data-gambar="{{ $url }}"
           data-sematan="{{ $sematan }}"
           data-judul="{{ $judul }}"
           data-tautan="{{ $tautan }}"
           {{-- Lewat window, bukan metode komponen: tabel ini berada di dalam
                komponen modal rincian yang juga punya keadaan "terbuka". --}}
           x-on:click="window.bukaPratinjauGambar($event)"
           {{-- Penanda loading tautan global dimatikan: kliknya membuka pratinjau,
                bukan berpindah halaman, jadi penandanya tidak akan pernah lepas. --}}
           data-no-link-loading
       @endif
       class="block shrink-0 rounded-lg transition hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
@endif

<div data-kotak-gambar class="relative {{ $ukuran }} shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
    <span class="absolute inset-0 flex items-center justify-center text-slate-400">
        <i class="ri-image-line text-lg"></i>
    </span>

    {{-- Tanpa URL tidak ada <img> sama sekali; src kosong tetap dianggap permintaan
         oleh browser dan menghasilkan gambar rusak. --}}
    @if($url)
        <img data-thumbnail src="{{ $url }}" alt="{{ $alt }}"
             loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()"
             class="absolute inset-0 h-full w-full object-cover">
    @endif
</div>

@if($dapatDiklik)
    </a>
@endif
