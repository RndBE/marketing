@extends('layouts.app', ['title' => 'Detail Bundle'])

@section('content')
    <a data-bundle-back-link href="{{ route('price_list.index') }}"
        class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900">
        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor"
            stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10H4.5m0 0 4-4m-4 4 4 4" />
        </svg>
        Kembali ke Daftar Bundle
    </a>

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-5">
        <div class="min-w-0 flex-1">
            <div class="text-xs text-slate-500">Bundle</div>
            <h1 class="text-xl font-semibold">{{ $product->nama }}</h1>
            <div class="mt-1 text-sm text-slate-500">{{ $product->kode ?? '-' }}</div>
            <div class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $product->deskripsi }}</div>
            <div class="mt-3 text-sm font-semibold text-slate-800">Harga Satuan Bundle: Rp
                {{ number_format((int) $unitPrice, 0, ',', '.') }}</div>
        </div>

        <div class="flex flex-wrap gap-2 md:shrink-0 md:flex-nowrap">
            <form method="POST" action="{{ route('price_list.duplicate', $product->id) }}" data-duplicate-submit
                data-confirm-title="Duplikat Bundle?"
                data-confirm-duplicate="Salinan baru akan dibuat dari bundle {{ $product->nama }}. Data bundle asli tidak berubah.">
                @csrf
                <button data-duplicate-button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-70">
                    <svg data-duplicate-spinner aria-hidden="true" class="hidden h-4 w-4 animate-spin"
                        viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor"
                            stroke-width="3" />
                        <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                    </svg>
                    <span data-duplicate-label data-idle-label="Duplikat"
                        data-loading-label="Menduplikat...">Duplikat</span>
                </button>
            </form>

            <a href="{{ route('price_list.edit', $product->id) }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Edit</a>

            <form method="POST" action="{{ route('price_list.destroy', $product->id) }}"
                data-confirm-title="Hapus Bundle?"
                data-confirm-delete="Bundle {{ $product->nama }} akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-3 font-semibold">Daftar Item Bundle</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border border-slate-200">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">No</th>
                                <th class="px-3 py-2 text-left font-semibold">Nama</th>
                                <th class="px-3 py-2 text-right font-semibold">Qty</th>
                                <th class="px-3 py-2 text-left font-semibold">Satuan</th>
                                <th class="px-3 py-2 text-right font-semibold">Harga</th>
                                <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="border border-slate-200 divide-y divide-slate-100">
                            @forelse($product->details as $d)
                                <tr>
                                    <td class="px-3 py-2">{{ $d->urutan }}</td>
                                    <td class="px-3 py-2">
                                        <div class="font-semibold">{{ $d->nama }}</div>
                                        @if ($d->spesifikasi)
                                            <div class="text-xs text-slate-500 mt-0.5 whitespace-pre-wrap">
                                                {{ $d->spesifikasi }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $d->qty, 2, ',', '.') }}</td>
                                    <td class="px-3 py-2">{{ $d->satuan }}</td>
                                    <td class="px-3 py-2 text-right">Rp {{ number_format((int) $d->harga, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold">Rp
                                        {{ number_format((int) $d->subtotal, 0, ',', '.') }}</td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-6 text-center text-slate-500">Belum ada item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
@endsection
