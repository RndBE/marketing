@extends('layouts.app', ['title' => 'Rincian Bahan'])

@section('content')
{{--
    Halaman penuh untuk rincian bahan.

    Jalur biasanya modal, tapi tautannya tetap tautan sungguhan: dibuka di tab baru
    atau tanpa JavaScript, halaman ini yang melayani. Badannya berkas yang sama
    dengan yang dipakai modal.
--}}
<div class="space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('harga-modal.index', ['tab' => $tipe->value]) }}"
               class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900">
                <i class="ri-arrow-left-line"></i>
                Kembali ke {{ $tipe->label() }}
            </a>

            <h1 class="text-2xl font-bold text-slate-900">
                {{ $namaProduk !== '' ? $namaProduk : 'Rincian Bahan' }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Bahan yang dipakai batch produksi
                <span class="font-medium text-slate-700">{{ $kodeProduksi !== '' ? $kodeProduksi : $produksiId }}</span>
                dari {{ $tipe->label() }}. Ditarik langsung dari inventory dan tidak disimpan di CRM.
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

    @include('harga_modal.partials.rincian_isi')
</div>
@endsection
