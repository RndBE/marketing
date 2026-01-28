@extends('layouts.app', ['title' => 'Detail Bundle'])

@section('content')
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-5">
        <div>
            <div class="text-xs text-slate-500">Bundle</div>
            <h1 class="text-xl font-semibold">{{ $product->nama }}</h1>
            <div class="text-sm text-slate-500 mt-1">{{ $product->kode ?? '-' }}</div>
            <div class="text-sm text-slate-600 mt-2 whitespace-pre-wrap">{{ $product->deskripsi }}</div>
            <div class="text-sm font-semibold text-slate-800 mt-3">Harga Satuan Bundle: Rp
                {{ number_format((int) $unitPrice, 0, ',', '.') }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('price_list.duplicate', $product->id) }}">
                @csrf
                <button
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Duplicate</button>
            </form>

            <a href="{{ route('price_list.edit', $product->id) }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Edit</a>

            <form method="POST" action="{{ route('price_list.destroy', $product->id) }}"
                onsubmit="return confirm('Hapus bundle ini?')">
                @csrf
                @method('DELETE')
                <button
                    class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold mb-3">Daftar Item Bundle</h2>

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
