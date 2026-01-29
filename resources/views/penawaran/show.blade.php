@extends('layouts.app', ['title' => 'Detail Penawaran'])

@section('content')
    @php
        $docNo = $penawaran->docNumber?->doc_no ?? 'PNW-' . str_pad((string) $penawaran->id, 6, '0', STR_PAD_LEFT);
        $total = 0;
        foreach ($penawaran->items as $it) {
            $total += (int) $it->subtotal;
        }

        $discountAmount = 0;

        if ($penawaran->discount_enabled) {
            $dv = (float) ($penawaran->discount_value ?? 0);
            $dt = $penawaran->discount_type ?? 'percent';

            if ($dt === 'percent') {
                $discountAmount = (int) round($total * ($dv / 100));
            } else {
                $discountAmount = (int) round($dv);
            }

            if ($discountAmount > $total) {
                $discountAmount = $total;
            }
        }

        $dpp = $total - $discountAmount;

        $taxAmount = 0;
        if ($penawaran->tax_enabled) {
            $tr = (float) ($penawaran->tax_rate ?? 11);
            $taxAmount = (int) round($dpp * ($tr / 100));
        }

        $grandTotal = $dpp + $taxAmount;
    @endphp

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-5">
        <div class="">
            <div class="text-xs text-slate-500">Nomor Dokumen</div>
            <h1 class="text-xl font-semibold">{{ $docNo }}</h1>
            <div class="text-sm text-slate-600 mt-1">{{ $penawaran->judul ?? '-' }}</div>

            <div class="text-sm font-semibold text-slate-700 mt-3">
                Total Penawaran sebelum pajak dan diskon : Rp {{ number_format((int) $total, 0, ',', '.') }}
            </div>
            <div class="text-sm font-semibold text-slate-700">
                Total Penawaran setelah pajak dan diskon : Rp {{ number_format((int) $grandTotal, 0, ',', '.') }}
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($penawaran->approval && $penawaran->approval->status == 'menunggu')
                <button onclick="openApprovalModal({{ $penawaran->approval->id }})"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg">
                    Persetujuan Penawaran
                </button>
            @endif
            <a href="{{ route('penawaran.pdf', $penawaran->id) }}"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Download
                PDF</a>
            <a href="{{ route('penawaran.edit', $penawaran->id) }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Edit</a>
            <form method="POST" action="{{ route('penawaran.destroy', $penawaran->id) }}"
                onsubmit="return confirm('Hapus penawaran ini?')">
                @csrf
                @method('DELETE')
                <button
                    class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 relative">
                <input id="toggle_bundle" type="checkbox"
                    class="peer absolute left-5 top-6 h-4 w-4 rounded border-slate-300 accent-slate-900">

                <div class="flex items-center justify-between gap-3 pl-7">
                    <div class="flex items-center gap-3">
                        <label for="toggle_bundle" class="cursor-pointer text-sm font-semibold select-none">
                            Tambah Bundle
                        </label>
                        <div class="text-xs text-slate-500 hidden md:block">Centang untuk buka form</div>
                    </div>


                </div>

                <div class="mt-4 hidden peer-checked:block">
                    <form method="POST" action="{{ route('penawaran.items.bundle', $penawaran->id) }}"
                        class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        @csrf

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Product</label>
                            <select name="product_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <option value="">Pilih product</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->kode ? $p->kode . ' - ' : '' }}{{ $p->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Qty</label>
                            <input name="qty" value="1"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Judul (opsional)</label>
                            <input name="judul"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Catatan (opsional)</label>
                            <input name="catatan"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>

                        <div class="md:col-span-5 flex justify-end">
                            <button
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Tambah Bundle
                            </button>
                        </div>
                    </form>
                </div>
            </div>




            <div class="rounded-2xl border border-slate-200 bg-white p-5 relative">
                <input id="toggle_custom" type="checkbox"
                    class="peer absolute left-5 top-6 h-4 w-4 rounded border-slate-300 accent-slate-900">

                <div class="flex items-center justify-between gap-3 pl-7">
                    <div class="flex items-center gap-3">
                        <label for="toggle_custom" class="cursor-pointer text-sm font-semibold select-none">
                            Tambah Item Custom
                        </label>
                        <div class="text-xs text-slate-500 hidden md:block">Centang untuk buka form</div>
                    </div>
                </div>

                <div class="mt-4 hidden peer-checked:block">
                    <form method="POST" action="{{ route('penawaran.items.custom', $penawaran->id) }}"
                        class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Judul</label>
                            <input name="judul"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Catatan</label>
                            <input name="catatan"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-3 flex justify-end">
                            <button
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Tambah Custom
                            </button>
                        </div>
                    </form>
                </div>
            </div>



            <div class="space-y-4">
                @forelse($penawaran->items as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 pb-4 pt-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                {{-- <div class="text-xs text-slate-500">
                                    {{ $item->tipe }}{{ $item->product?->nama ? ' • ' . $item->product->nama : '' }}
                                </div> --}}
                                <div class="text-lg font-semibold">{{ $item->judul }}</div>
                                @if ($item->catatan)
                                    <div class="text-sm text-slate-600 mt-1">{{ $item->catatan }}</div>
                                @endif
                                @php
                                    $qtyBundle = (float) ($item->qty ?? 1);
                                    $unitPrice = 0;
                                    if ($item->tipe === 'bundle') {
                                        foreach ($item->details as $d) {
                                            $unitPrice += (int) ($d->harga ?? 0);
                                        }
                                    }
                                @endphp

                                @if ($item->tipe === 'bundle')
                                    <div class="mt-2 text-sm text-slate-600">
                                        Harga Satuan :
                                        <span class="font-semibold">Rp
                                            {{ number_format((int) $unitPrice, 0, ',', '.') }}</span>
                                        <span class="text-slate-400">•</span>
                                        Qty :
                                        <span class="font-semibold">{{ number_format($qtyBundle, 2, ',', '.') }}</span>
                                        <span class="text-slate-400">•</span>
                                        Total:
                                        <span class="font-semibold">Rp
                                            {{ number_format((int) $item->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                @endif

                            </div>
                            <form method="POST"
                                action="{{ route('penawaran.items.delete', [$penawaran->id, $item->id]) }}"
                                onsubmit="return confirm('Hapus item ini?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus
                                    Item</button>
                            </form>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border border-slate-200">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">No</th>
                                        <th class="px-3 py-2 text-left font-semibold">Rincian</th>
                                        <th class="px-3 py-2 text-right font-semibold">Qty</th>
                                        <th class="px-3 py-2 text-left font-semibold">Satuan</th>
                                        <th class="px-3 py-2 text-right font-semibold">Harga</th>
                                        <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                                        <th class="px-3 py-2 text-right font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border border-slate-200 divide-y divide-slate-100">
                                    @forelse($item->details as $d)
                                        <tr>
                                            <td class="px-3 py-2">{{ $d->urutan }}</td>
                                            <td class="px-3 py-2">
                                                <div class="font-semibold">{{ $d->nama }}</div>
                                                @if ($d->spesifikasi)
                                                    <div class="text-xs text-slate-500 mt-0.5 whitespace-nowrap">
                                                        {{ $d->spesifikasi }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right  whitespace-nowrap">
                                                {{ number_format((float) $d->qty, 2, ',', '.') }}</td>
                                            <td class="px-3 py-2  whitespace-nowrap">{{ $d->satuan }}</td>
                                            <td class="px-3 py-2 text-right  whitespace-nowrap">Rp
                                                {{ number_format((int) $d->harga, 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">Rp
                                                {{ number_format((int) $d->subtotal, 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <details class="inline-block text-left  whitespace-nowrap">
                                                    <summary
                                                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                                        Edit</summary>
                                                    <div
                                                        class="mt-2 w-[360px] rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
                                                        <form method="POST"
                                                            action="{{ route('penawaran.item_details.update', [$penawaran->id, $item->id, $d->id]) }}"
                                                            class="space-y-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input name="nama" value="{{ $d->nama }}"
                                                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                                            <div class="grid grid-cols-3 gap-2">
                                                                <input name="qty" value="{{ $d->qty }}"
                                                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                                                <input name="satuan" value="{{ $d->satuan }}"
                                                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                                                <input name="harga" value="{{ $d->harga }}"
                                                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                                            </div>
                                                            <textarea name="spesifikasi" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ $d->spesifikasi }}</textarea>
                                                            <div class="flex items-center justify-between">
                                                                <form></form>
                                                                <form method="POST"
                                                                    action="{{ route('penawaran.item_details.delete', [$penawaran->id, $item->id, $d->id]) }}"
                                                                    onsubmit="return confirm('Hapus detail?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button
                                                                        class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                                                </form>
                                                                <button
                                                                    class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">Belum ada
                                                rincian.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 relative">
                            <input id="toggle_detail_{{ $item->id }}" type="checkbox"
                                class="peer absolute left-4 top-4 h-4 w-4 rounded border-slate-300 accent-slate-900">

                            <div class="flex items-center justify-between gap-3 pl-6">
                                <div class="flex items-center gap-3">
                                    <label for="toggle_detail_{{ $item->id }}"
                                        class="cursor-pointer text-sm font-semibold select-none">
                                        Tambah Rincian
                                    </label>
                                    <div class="text-xs text-slate-500 hidden md:block mb-0">Centang untuk buka form</div>
                                </div>
                            </div>

                            <div class="mt-4 hidden peer-checked:block">
                                <form method="POST"
                                    action="{{ route('penawaran.item_details.add', [$penawaran->id, $item->id]) }}"
                                    class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                    @csrf
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold mb-1">Nama</label>
                                        <input name="nama"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold mb-1">Spesifikasi</label>
                                        <input name="spesifikasi"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Qty</label>
                                        <input name="qty" value="1"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Satuan</label>
                                        <input name="satuan"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold mb-1">Harga</label>
                                        <input name="harga" value="0"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div class="md:col-span-4 flex items-end justify-end">
                                        <button
                                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                            Tambah Detail
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>


                    </div>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 text-slate-500">Belum ada item.</div>
                @endforelse
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="font-semibold mb-3">Keterangan</div>

                @php
                    $termsSorted = $penawaran->terms->sortBy(
                        fn($x) => ($x->parent_id ?? 0) . '-' . $x->urutan . '-' . $x->id,
                    );

                    $termsByParent = $termsSorted->groupBy(
                        fn($t) => is_null($t->parent_id) ? 'root' : (string) $t->parent_id,
                    );

                    $roots = $termsByParent['root'] ?? collect();

                    $termOptions = [];
                    $walk = function ($parentKey, $prefix) use (&$walk, &$termOptions, $termsByParent) {
                        $children = $termsByParent[$parentKey] ?? collect();
                        foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $t) {
                            $termOptions[] = [
                                'id' => $t->id,
                                'label' => $prefix . \Illuminate\Support\Str::limit(trim((string) $t->isi), 60),
                            ];
                            $walk((string) $t->id, $prefix . '— ');
                        }
                    };
                    $walk('root', '');
                @endphp


                <form method="POST" action="{{ route('penawaran.terms.add', $penawaran->id) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-semibold mb-1">Parent (opsional)</label>
                            <select name="parent_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <option value="">Jadikan utama</option>
                                @foreach ($termOptions as $opt)
                                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold mb-1">Isi</label>
                            <div class="flex gap-2">
                                <textarea name="isi" rows="2"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm" placeholder="Isi keterangan..."></textarea>
                                <button
                                    class="shrink-0 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="mt-4 space-y-2">
                    @php
                        $termsByParent = $penawaran->terms->groupBy('parent_id');
                        $roots = $termsByParent[null] ?? collect();
                    @endphp
                    @forelse ($roots as $t)
                        @include('penawaran.partials.term_node', [
                            'penawaran' => $penawaran,
                            'term' => $t,
                            'termsByParent' => $termsByParent,
                            'level' => 0,
                        ])
                    @empty
                        <div class="text-sm text-slate-500">Belum ada keterangan.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 relative">
                <form method="POST" action="{{ route('penawaran.pricing.upsert', $penawaran->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between">
                        <div class="font-semibold">Diskon & Pajak</div>
                        <button
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Simpan
                        </button>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 relative">
                        <input id="discount_enabled" type="checkbox" name="discount_enabled" value="1"
                            class="peer absolute left-4 top-5 h-4 w-4 rounded border-slate-300 accent-slate-900"
                            {{ $penawaran->discount_enabled ? 'checked' : '' }}>

                        <div class="pl-7">
                            <label for="discount_enabled" class="cursor-pointer text-sm font-semibold select-none">
                                Aktifkan Diskon
                            </label>
                        </div>

                        <div class="mt-3 hidden peer-checked:block pl-10">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Tipe</label>
                                    <select name="discount_type"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                        <option value="percent"
                                            {{ ($penawaran->discount_type ?? 'percent') === 'percent' ? 'selected' : '' }}>
                                            %
                                        </option>
                                        <option value="fixed"
                                            {{ ($penawaran->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>
                                            Rp
                                        </option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold mb-1">Nilai</label>
                                    <input name="discount_value" value="{{ $penawaran->discount_value ?? 0 }}"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 relative">
                        <input id="tax_enabled" type="checkbox" name="tax_enabled" value="1"
                            class="peer absolute left-4 top-5 h-4 w-4 rounded border-slate-300 accent-slate-900"
                            {{ $penawaran->tax_enabled ? 'checked' : '' }}>

                        <div class="pl-7">
                            <label for="tax_enabled" class="cursor-pointer text-sm font-semibold select-none">
                                Aktifkan Pajak
                            </label>
                        </div>

                        <div class="mt-3 hidden peer-checked:block pl-10">
                            <label class="block text-xs font-semibold mb-1">Tarif Pajak (%)</label>
                            <input name="tax_rate" value="{{ $penawaran->tax_rate ?? 11 }}"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>
                    </div>

                </form>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold mb-3">Keterangan Penawaran</h2>

                <form method="POST" action="{{ route('penawaran.keterangan.upsert', $penawaran->id) }}"
                    class="space-y-2">
                    @csrf

                    <label class="block text-xs font-semibold">Instansi Tujuan</label>
                    <input name="instansi_tujuan"
                        value="{{ old('instansi_tujuan', $penawaran->instansi_tujuan ?? $penawaran->pic?->instansi) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Instansi tujuan">

                    <label class="block text-xs font-semibold">Nama Pekerjaan</label>
                    <input name="nama_pekerjaan" value="{{ old('nama_pekerjaan', $penawaran->nama_pekerjaan) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nama pekerjaan">

                    <label class="block text-xs font-semibold">Lokasi</label>
                    <input name="lokasi_pekerjaan" value="{{ old('lokasi_pekerjaan', $penawaran->lokasi_pekerjaan) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Lokasi pekerjaan">

                    <label class="block text-xs font-semibold">No. Penawaran</label>
                    <input value="{{ $docNo }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-slate-50" readonly>

                    <label class="block text-xs font-semibold">Tanggal Penawaran</label>
                    <input type="date" name="tanggal_penawaran"
                        value="{{ old('tanggal_penawaran', optional($penawaran->tanggal_penawaran)->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">

                    <button
                        class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan Keterangan
                    </button>
                </form>
            </div>



            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold mb-3">Tanda Tangan</h2>
                <form method="POST" action="{{ route('penawaran.signatures.add', $penawaran->id) }}"
                    enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <input name="nama" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Nama">
                    <input name="jabatan" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Jabatan">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="kota" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                            placeholder="Kota">
                        <input type="date" name="tanggal"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <input type="file" name="ttd"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    <button
                        class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Tambah</button>
                </form>

                <div class="mt-3 space-y-2">
                    @foreach ($penawaran->signatures as $sg)
                        <div class="rounded-2xl border border-slate-200 p-4 flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $sg->nama }}</div>
                                <div class="text-sm text-slate-600">{{ $sg->jabatan }}</div>
                                <div class="text-xs text-slate-500">{{ $sg->kota }} {{ $sg->tanggal }}</div>
                            </div>
                            <form method="POST"
                                action="{{ route('penawaran.signatures.delete', [$penawaran->id, $sg->id]) }}"
                                onsubmit="return confirm('Hapus tanda tangan?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>



            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold mb-3">Lampiran</h2>
                <form method="POST" action="{{ route('penawaran.attachments.add', $penawaran->id) }}"
                    enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <input name="judul" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Judul (opsional)">
                    <input type="file" name="file"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    <button
                        class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Upload</button>
                </form>

                <div class="mt-3 space-y-2">
                    @foreach ($penawaran->attachments as $a)
                        <div class="rounded-2xl border border-slate-200 p-4 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold truncate">{{ $a->judul ?? 'Lampiran' }}</div>
                                <div class="text-xs text-slate-500 truncate">{{ $a->file_path }}</div>
                            </div>
                            <form method="POST"
                                action="{{ route('penawaran.attachments.delete', [$penawaran->id, $a->id]) }}"
                                onsubmit="return confirm('Hapus lampiran?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div id="approvalModal" class=".hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white w-full max-w-md rounded-xl p-6">
            <h2 class="text-lg font-semibold mb-4">Proses Approval</h2>

            <form method="POST" action="{{ route('approval.process') }}">
                @csrf
                <input type="hidden" name="approval_id" id="modal_approval_id">

                <textarea name="catatan" class="w-full border rounded-lg p-2 mb-4" placeholder="Catatan"></textarea>

                <div class="flex justify-end gap-2">
                    <button name="aksi" value="reject"
                        class="bg-red-500 text-white px-4 py-2 rounded-lg">Tolak</button>
                    <button name="aksi" value="approve"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg">Setujui</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApprovalModal(id) {
            document.getElementById('modal_approval_id').value = id;
            document.getElementById('approvalModal').classList.remove('hidden');
        }
    </script>

@endsection
