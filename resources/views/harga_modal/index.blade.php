@extends('layouts.app', ['title' => 'Harga Modal'])

@section('content')
@php
    use App\Services\Inventory\TabHargaModal;

    // Kata pencarian dan saringan stok ikut terbawa saat pindah tab. `sumber` dan
    // `menyimpang` sengaja ditinggal: nilai sumber berbeda di tiap tab, dan
    // simpangan hanya ada di tab Bahan -- ikut membawanya hanya akan menghasilkan
    // tabel kosong tanpa sebab yang jelas.
    $tautanTab = fn (TabHargaModal $pilihan) => route('harga-modal.index', array_filter([
        'tab' => $pilihan->value,
        'cari' => $saringan->cari,
        'hanya_tersedia' => $hanyaTersedia ? 1 : null,
    ], fn ($nilai) => $nilai !== null && $nilai !== ''));
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Harga Modal</h1>
            <p class="mt-1 text-sm text-slate-500">
                Harga modal (HPP) dari inventory untuk <span class="font-medium text-slate-700">{{ $email }}</span>.
                Data ditarik langsung saat halaman dibuka dan tidak disimpan di CRM.
            </p>
        </div>

        @if($hasil->diambilPada)
            <span class="inline-flex flex-shrink-0 items-center gap-1.5 self-start rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm">
                <i class="ri-time-line text-sm"></i>
                data per {{ $hasil->diambilPada->timezone(config('app.timezone'))->translatedFormat('j F Y, H:i') }}
                {{ $hasil->diambilPada->timezone(config('app.timezone'))->format('T') }}
            </span>
        @endif
    </div>

    {{-- Tab. Berpindah tab berarti satu permintaan baru ke server, yang menarik
         data tab itu saja. Tanpa ini, sekali buka halaman menyeret ketiga tab. --}}
    <nav role="tablist" aria-label="Kategori" class="inline-flex gap-1 rounded-xl bg-slate-200/70 p-1">
        @foreach(TabHargaModal::cases() as $pilihan)
            <a href="{{ $tautanTab($pilihan) }}" role="tab"
               aria-selected="{{ $tab === $pilihan ? 'true' : 'false' }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold transition
               {{ $tab === $pilihan ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                {{ $pilihan->label() }}
            </a>
        @endforeach
    </nav>

    @if(! $hasil->berhasil)
        @include('harga_modal.partials.pesan_gagal')
    @else
        @if($hasil->hargaModalTidakAda())
            <div class="flex items-start gap-3 rounded-2xl border border-slate-300 bg-slate-50 p-4">
                <i class="ri-information-line mt-0.5 text-lg text-slate-500"></i>
                <div class="text-sm text-slate-700">
                    <p class="font-semibold">Nilai harga modal tidak ada di jawaban inventory.</p>
                    <p class="mt-0.5 leading-relaxed">
                        Barisnya terkirim, tapi tidak satu pun membawa angka harga modal, sehingga kolom harga
                        tetap kosong. Bidang yang dibaca CRM adalah <code>harga_modal_satuan</code>.
                    </p>
                </div>
            </div>
        @endif

        @if($hasil->kosong())
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                <p class="text-sm text-slate-500">
                    Tidak ada baris pada tab {{ $tab->label() }}.
                </p>

                @if($hanyaTersedia)
                    <p class="mt-1 text-sm text-slate-500">
                        Saringan "Hanya yang stoknya tersedia" sedang menyala.
                        <a href="{{ route('harga-modal.index', ['tab' => $tab->value]) }}"
                           class="font-medium text-slate-900 underline">Matikan saringan</a>
                        untuk melihat seluruh baris.
                    </p>
                @endif
            </div>
        @else
            @php
                // Disusun dari seluruh baris tab, bukan dari halaman yang sedang tampil,
                // supaya pilihan sumbernya tidak menyusut saat orang berpindah halaman.
                $sumberTersedia = collect($hasil->baris)
                    ->pluck('sumber')
                    ->filter(fn ($nilai) => is_string($nilai) && trim($nilai) !== '')
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $simpangan = $tab->berbasisUnit() ? null : $hasil->ringkasSimpangan();
            @endphp

            <div class="space-y-4">
                {{-- Bilah saringan berada di luar kontainer bergulir milik tabel, jadi
                     tidak ikut tergeser atau terpotong saat tabel yang lebar digulir. --}}
                @include('harga_modal.partials.saringan', [
                    'sumberTersedia' => $sumberTersedia,
                    'labelBaris' => $tab->berbasisUnit() ? 'baris' : 'bahan',
                ])

                @if($simpangan && $simpangan['menyimpang'] > 0)
                    @php
                        // Dihitung atas seluruh bahan di tab ini, bukan atas satu halaman.
                        $peringatan = number_format($simpangan['menyimpang'], 0, ',', '.')
                            . ' dari ' . number_format($simpangan['berstok'], 0, ',', '.')
                            . ' bahan berstok punya selisih di atas 20% dari rata-rata tertimbang.';
                    @endphp
                    <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <i class="ri-error-warning-line mt-0.5 text-lg text-amber-600"></i>
                        <div class="text-sm text-amber-900">
                            <p class="font-semibold">{{ $peringatan }}</p>
                            <p class="mt-0.5 leading-relaxed">
                                Barisnya ditandai di kolom Rata-rata Tertimbang, dan bisa disaring lewat kotak
                                "Hanya selisih &ge;20%" di atas. Untuk bahan-bahan itu, memakai satu angka saja akan
                                meleset -- pilih sesuai keperluan: harga terakhir untuk pembelian berikutnya,
                                rata-rata tertimbang untuk stok yang sudah ada.
                            </p>
                        </div>
                    </div>
                @endif

                @if($jumlahTersaring === 0)
                    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                        <p class="text-sm text-slate-500">
                            Tidak ada {{ $tab->berbasisUnit() ? 'baris' : 'bahan' }} yang cocok dengan pencarian atau saringan.
                        </p>
                        <a href="{{ route('harga-modal.index', ['tab' => $tab->value]) }}"
                           class="mt-1 inline-block text-sm font-medium text-slate-900 underline">
                            Bersihkan saringan
                        </a>
                    </div>
                @else
                    @if($tab->berbasisUnit())
                        {{-- Bilah margin dan tabel berbagi satu komponen: mengubah margin
                             global harus langsung mengubah seluruh baris. --}}
                        <div x-data="marginHargaJual" class="space-y-4">
                            @include('harga_modal.partials.margin_bar', [
                                'marginBawaan' => \App\Services\Inventory\MarginHargaJual::MARGIN_BAWAAN,
                                'kelipatan' => \App\Services\Inventory\MarginHargaJual::KELIPATAN,
                            ])

                            @include('harga_modal.partials.tabel', [
                                'baris' => $halaman->items(),
                                'marginBawaan' => \App\Services\Inventory\MarginHargaJual::MARGIN_BAWAAN,
                            ])
                        </div>

                        {{-- Bersaudara, bukan bersarang: komponen yang saling membungkus
                             pernah membuat keadaan tertulis ke komponen yang salah. --}}
                        @include('harga_modal.partials.modal_rincian')
                    @else
                        @include('harga_modal.partials.tabel_bahan', [
                            'baris' => $halaman->items(),
                            'tampilkanHarga' => ! $hasil->hargaModalTidakAda(),
                        ])
                    @endif

                    {{-- Sengaja bersaudara, bukan membungkus. Sewaktu ia membungkus
                         tabel, penulisan keadaan dari dalam komponen modal rincian
                         mendarat di komponen yang salah dan klik foto justru membuka
                         rincian bahan. Permintaannya sekarang lewat event window. --}}
                    @include('harga_modal.partials.modal_gambar')

                    @if($halaman->hasPages())
                        <div>{{ $halaman->links() }}</div>
                    @endif
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
