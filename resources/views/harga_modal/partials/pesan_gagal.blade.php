{{--
    Kartu pesan ketika panggilan ke inventory tidak berhasil.

    Tiap sebab tampil beda supaya orang tahu apa yang harus dilakukan: minta akses,
    daftarkan email, atau lapor ke tim teknis. Meratakan semuanya jadi "error"
    membuat ketiganya terlihat seperti masalah yang sama.

    Dibutuhkan: $hasil
--}}
@php
    use App\Services\Inventory\HasilHargaModal;

    $gaya = [
        HasilHargaModal::JENIS_AKSES => [
            'bingkai' => 'border-amber-200 bg-amber-50',
            'ikon' => 'ri-lock-2-line text-amber-600',
            'teks' => 'text-amber-900',
            'judul' => 'Akses ditolak',
            'lanjutan' => 'Hak lihat harga modal diatur di sisi inventory. Hubungi tim inventory kalau Anda memang seharusnya punya akses.',
        ],
        HasilHargaModal::JENIS_TIDAK_TERDAFTAR => [
            'bingkai' => 'border-sky-200 bg-sky-50',
            'ikon' => 'ri-user-search-line text-sky-600',
            'teks' => 'text-sky-900',
            'judul' => 'Email belum terdaftar',
            'lanjutan' => 'Minta tim inventory mendaftarkan email ini supaya datanya bisa ditarik.',
        ],
        HasilHargaModal::JENIS_TEKNIS => [
            'bingkai' => 'border-rose-200 bg-rose-50',
            'ikon' => 'ri-plug-line text-rose-600',
            'teks' => 'text-rose-900',
            'judul' => 'Gangguan teknis',
            'lanjutan' => 'Ini bukan soal hak akses Anda. Sampaikan pesan di atas apa adanya ke tim teknis.',
        ],
    ];

    $tampilan = $gaya[$hasil->jenisPesan] ?? $gaya[HasilHargaModal::JENIS_TEKNIS];
@endphp

<div class="flex items-start gap-3 rounded-2xl border p-5 {{ $tampilan['bingkai'] }}">
    <i class="{{ $tampilan['ikon'] }} mt-0.5 text-xl"></i>
    <div class="{{ $tampilan['teks'] }}">
        <p class="text-sm font-semibold">{{ $tampilan['judul'] }}</p>
        <p class="mt-1 text-sm leading-relaxed">{{ $hasil->pesan }}</p>
        <p class="mt-2 text-xs leading-relaxed opacity-80">{{ $tampilan['lanjutan'] }}</p>
    </div>
</div>
