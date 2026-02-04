@extends('layouts.app', ['title' => 'Detail Invoice'])

@section('content')
    @php
        $canEdit = auth()->user()->roles->contains('slug', 'admin') || $invoice->user_id === auth()->id();
    @endphp
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-5">
        <div>
            <div class="text-xs text-slate-500">Nomor Invoice</div>
            <h1 class="text-xl font-semibold">{{ $invoice->docNumber?->doc_no }}</h1>
            <div class="text-sm text-slate-600 mt-1">{{ $invoice->judul }}</div>
            <div class="mt-2 flex items-center gap-2">
                @if ($invoice->status === 'draft')
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-400 text-gray-100">Draft</span>
                @elseif($invoice->status === 'sent')
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-600">Sent</span>
                @elseif($invoice->status === 'paid')
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-600">Paid</span>
                @elseif($invoice->status === 'cancelled')
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-600">Cancelled</span>
                @endif
            </div>

            <div class="mt-4 text-sm font-semibold text-slate-700">
                Total Tagihan: Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Kembali
            </a>
            <a href="{{ route('invoices.pdf', $invoice->id) }}" target="_blank"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Download PDF
            </a>
            <a href="{{ route('invoices.edit', $invoice->id) }}"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Edit Invoice
            </a>
            <form method="POST" action="{{ route('invoices.destroy', $invoice->id) }}"
                onsubmit="return confirm('Hapus invoice ini secara permanen?')">
                @csrf
                @method('DELETE')
                <button
                    class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">

            @if ($invoice->parent_id)
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 mb-4">
                    <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Invoice Turunan (Termin)
                    </div>
                    <div class="flex items-center gap-2 text-sm text-blue-800">
                        <span>Bagian dari Invoice Induk:</span>
                        <a href="{{ route('invoices.show', $invoice->parent_id) }}"
                            class="font-bold underline hover:text-blue-900">
                            {{ $invoice->parent->docNumber?->doc_no ?? 'Parent Invoice' }}
                        </a>
                    </div>
                </div>
            @endif

            @if (is_null($invoice->parent_id))
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="">
                        <h3 class="font-semibold text-sm mb-2">Tambah Item</h3>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="document.getElementById('form_bundle').classList.toggle('hidden')"
                                class="text-xs px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg font-semibold">
                                + Bundle Product
                            </button>
                            <button onclick="document.getElementById('form_custom').classList.toggle('hidden')"
                                class="text-xs px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg font-semibold">
                                + Custom Item
                            </button>
                        </div>
                    </div>


                    <div id="form_bundle" class="hidden p-4 border rounded-xl bg-slate-50 mt-4">
                        <form method="POST" action="{{ route('invoices.items.bundle', $invoice->id) }}"
                            class="flex gap-2 items-end">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs font-semibold mb-1">Pilih Produk</label>
                                <select name="product_id" class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->kode }} - {{ $p->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-24">
                                <label class="block text-xs font-semibold mb-1">Qty</label>
                                <input name="qty" value="1" type="number" step="0.01"
                                    class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            </div>
                            <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold">Tambah</button>
                        </form>
                    </div>


                    <div id="form_custom" class="hidden mt-4 p-4 border rounded-xl bg-slate-50" x-data="{ isSingle: false }">
                        <form method="POST" action="{{ route('invoices.items.custom', $invoice->id) }}">
                            @csrf
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Judul Item</label>
                                    <input name="judul" class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                        placeholder="Nama item group..." required>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="chk_single" name="is_single" value="1" x-model="isSingle"
                                        class="rounded border-slate-300">
                                    <label for="chk_single" class="text-xs font-semibold cursor-pointer">
                                        Langsung Input Harga (Tanpa Child Detail)
                                    </label>
                                </div>

                                <div class="grid grid-cols-3 gap-3" x-show="isSingle" x-transition>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Harga Satuan</label>
                                        <input name="price" type="number"
                                            class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2" placeholder="0">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Qty</label>
                                        <input name="qty" type="number" step="0.01" value="1"
                                            class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Satuan</label>
                                        <input name="unit" type="text" value="ls"
                                            class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                            placeholder="ls/pcs">
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold">Tambah
                                        Item</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif


            @if (is_null($invoice->parent_id))
                <div class="rounded-2xl border border-slate-200 bg-white p-5 relative" x-data="{ open: true }">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="text-sm font-semibold">Daftar Termin Pembayaran</div>
                            <div class="text-xs text-slate-500">Invoice Turunan</div>
                        </div>
                    </div>


                    @if ($invoice->children->count() > 0)
                        <div class="mb-5 overflow-hidden rounded-xl border border-slate-200">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-500 font-semibold border-b">
                                    <tr>
                                        <th class="px-4 py-2">No. Invoice</th>
                                        <th class="px-4 py-2">Judul / Keterangan</th>
                                        <th class="px-4 py-2 text-right">Nominal</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($invoice->children as $child)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2 font-medium">
                                                <a href="{{ route('invoices.show', $child->id) }}"
                                                    class="text-blue-600 hover:underline">
                                                    {{ $child->docNumber?->doc_no ?? 'DRAFT' }}
                                                </a>
                                                <div class="text-[10px] text-slate-400">
                                                    {{ $child->tgl_invoice->format('d/m/Y') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-2">
                                                {{ $child->judul }}
                                            </td>
                                            <td class="px-4 py-2 text-right font-medium">
                                                Rp {{ number_format($child->grand_total, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2">
                                                {{ ucfirst($child->status) }}
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                <a href="{{ route('invoices.show', $child->id) }}"
                                                    class="text-xs border px-2 py-1 rounded hover:bg-white">Lihat</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($canEdit)
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="text-xs font-semibold mb-3 uppercase tracking-wider text-slate-500">Buat Termin
                                Baru</div>
                            <form method="POST" action="{{ route('invoices.store_termin', $invoice->id) }}"
                                class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                @csrf

                                <div class="">
                                    <label class="block text-xs font-semibold mb-1">Nama Termin</label>
                                    <input name="termin_name" type="text"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                                        placeholder="e.g. Termin 1" required>
                                </div>

                                <div class="">
                                    <label class="block text-xs font-semibold mb-1">Persentase (%)</label>
                                    <input name="termin_percent" type="number" min="0.1" max="100" step="0.1"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                                        placeholder="e.g. 30" required>
                                </div>

                                <div class="">
                                    <label class="block text-xs font-semibold mb-1">Jatuh Tempo</label>
                                    <div class="grid grid-cols-2 gap-1">
                                        <input name="tgl_invoice" type="date" value="{{ date('Y-m-d') }}"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-2 py-2 text-sm" required>
                                        <input name="jatuh_tempo" type="date" value="{{ date('Y-m-d', strtotime('+7 days')) }}"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-2 py-2 text-sm" required>
                                    </div>
                                </div>

                                <div class="flex items-end items-end">
                                    <button
                                        class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                        Generate Invoice
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif


            @foreach ($invoice->items as $item)
                @php
                    $isBundle = $item->tipe === 'bundle';
                    $bundleUnitPrice = $isBundle ? $item->details->sum('subtotal') : 0;
                    $bundleQty = $item->qty ?: 1;
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-1">
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                {{ $isBundle ? 'Bundle Product' : 'Custom Item' }}
                            </div>
                            <div class="text-lg font-semibold text-slate-900">{{ $item->judul }}</div>
                            @if ($isBundle)
                                <div class="text-sm text-slate-600">
                                    Harga Satuan:
                                    <span class="font-semibold">Rp {{ number_format($bundleUnitPrice, 0, ',', '.') }}</span>
                                    <span class="text-slate-400">•</span>
                                    Qty:
                                    <span class="font-semibold">{{ number_format((float) $bundleQty, 2, ',', '.') }}</span>
                                    <span class="text-slate-400">•</span>
                                    Total:
                                    <span class="font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>

                        @if ($canEdit)
                            <div class="flex items-center gap-2">
                                <details class="relative">
                                    <summary
                                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                        Edit {{ $isBundle ? 'Bundle' : 'Item' }}
                                    </summary>
                                    <div
                                        class="mt-2 w-[340px] rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
                                        <form method="POST"
                                            action="{{ route('invoices.items.update', [$invoice->id, $item->id]) }}"
                                            class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="block text-xs font-semibold mb-1">Nama Item / Bundle</label>
                                                <input name="judul" value="{{ $item->judul }}" required
                                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                            </div>
                                            @if ($isBundle)
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-xs font-semibold mb-1">Qty</label>
                                                        <input name="qty" type="number" step="0.01"
                                                            value="{{ $bundleQty }}" required
                                                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold mb-1">Satuan</label>
                                                        <input name="satuan" type="text" value="{{ $item->satuan ?? 'ls' }}"
                                                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="flex justify-end">
                                                <button type="submit"
                                                    class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                                    Simpan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </details>

                                <form method="POST"
                                    action="{{ route('invoices.items.delete', [$invoice->id, $item->id]) }}"
                                    onsubmit="return confirm('Hapus item ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-y border-slate-100 text-slate-500">
                                <tr>
                                    <th class="text-left font-medium px-3 py-2">Deskripsi</th>
                                    <th class="text-right font-medium px-3 py-2 w-20">Qty</th>
                                    <th class="text-right font-medium px-3 py-2 w-32">Harga</th>
                                    <th class="text-right font-medium px-3 py-2 w-32">Total</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach ($item->details as $detail)
                                    <tr class="group">
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-800">{{ $detail->nama }}</div>
                                            @if ($detail->spesifikasi)
                                                <div class="text-xs text-slate-500">{{ $detail->spesifikasi }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            {{ $detail->qty }} {{ $detail->satuan }}
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            Rp {{ number_format($detail->harga, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            @if ($canEdit)
                                                <form method="POST"
                                                    action="{{ route('invoices.item_details.delete', [$invoice->id, $item->id, $detail->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        class="text-rose-400 hover:text-rose-600 opacity-0 group-hover:opacity-100">
                                                        &times;
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($canEdit)
                        <form method="POST" action="{{ route('invoices.item_details.add', [$invoice->id, $item->id]) }}"
                            class="mt-4 bg-slate-50 rounded-xl p-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            @csrf
                            <div class="md:col-span-2">
                                <input name="nama" placeholder="Nama item/jasa"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-1.5" required>
                            </div>
                            <div>
                                <input name="qty" type="number" step="0.01" placeholder="Qty"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-1.5" required>
                            </div>
                            <div class="flex gap-2">
                                <input name="harga" type="number" placeholder="Harga"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-1.5" required>
                                <button type="submit"
                                    class="bg-slate-800 text-white px-3 py-1.5 rounded-lg text-sm font-semibold">+</button>
                            </div>
                        </form>
                    @endif

                    <div class="mt-3 text-right font-semibold text-slate-700 border-t pt-2">
                        Subtotal Item: Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                    </div>
                </div>
            @endforeach
        </div>




        <div>
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5  ">
                <h3 class="font-semibold mb-3">Tanda Tangan</h3>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    {{-- Template Loader --}}
                    <form action="{{ route('invoices.load.signature', $invoice->id) }}" method="POST"
                        class="mb-4 flex gap-2">
                        @csrf
                        <select name="template_id" class="text-xs rounded-xl border-slate-300 flex-grow">
                            <option value="">-- Pilih Template TTD --</option>
                            @foreach($signatureTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->template_name }} ({{ $tpl->nama }})</option>
                            @endforeach
                        </select>
                        <button class="bg-blue-600 text-white px-3 py-1 rounded-xl text-xs font-bold">Load</button>
                    </form>

                    <form action="{{ route('invoices.signatures.save', $invoice->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1">Nama Penanda Tangan</label>
                                <input name="nama"
                                    value="{{ old('nama', $invoice->signature?->nama ?? auth()->user()->name) }}"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1">Jabatan</label>
                                <input name="jabatan"
                                    value="{{ old('jabatan', $invoice->signature?->jabatan ?? 'Marketing') }}"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1">Kota</label>
                                <input name="kota" value="{{ old('kota', $invoice->signature?->kota ?? 'Sleman') }}"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1">Tanggal</label>
                                <input type="date" name="tanggal"
                                    value="{{ old('tanggal', $invoice->signature?->tanggal ? $invoice->signature->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1">File TTD (Optional)</label>
                                @if($invoice->signature && $invoice->signature->ttd_path)
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="{{ asset('storage/' . $invoice->signature->ttd_path) }}"
                                            class="h-10 w-10 object-contain border rounded bg-white">
                                        <div class="text-xs text-slate-500">File saat ini terlampir. Upload baru untuk
                                            mengganti.</div>
                                    </div>
                                @endif
                                <input type="file" name="ttd" accept="image/*"
                                    class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="submit"
                                class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-800">
                                {{ $invoice->signature ? 'Update Tanda Tangan' : 'Simpan Tanda Tangan' }}
                            </button>
                        </div>
                    </form>

                    @if($invoice->signature)
                        <form action="{{ route('invoices.signatures.delete', $invoice->id) }}" method="POST"
                            class="mt-2 text-right" onsubmit="return confirm('Hapus tanda tangan?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-rose-500 hover:underline">Hapus Tanda Tangan</button>
                        </form>
                    @endif
                </div>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 mt-3">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold">Syarat & Ketentuan (Terms)</h3>


                    <form action="{{ route('invoices.load.term', $invoice->id) }}" method="POST" class="flex gap-2">
                        @csrf
                        <select name="template_id" class="text-xs rounded-xl border-slate-300">
                            <option value="">-- Load Template --</option>
                            @foreach($termTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->template_name }}</option>
                            @endforeach
                        </select>
                        <button class="bg-slate-800 text-white px-2 py-1 rounded-xl text-xs">Load</button>
                    </form>
                </div>

                <ul class="list-decimal list-outside ml-4 text-sm text-slate-600 space-y-1" id="terms-list">
                    @foreach($invoice->terms as $term)
                        <li id="term-row-{{ $term->id }}" class="group relative pr-8">
                            <span>{{ $term->isi }}</span>
                            <button onclick="deleteTerm({{ $term->id }})"
                                class="absolute right-0 top-0 text-red-400 opacity-0 group-hover:opacity-100 hover:text-red-600 font-bold px-2">×</button>
                        </li>
                    @endforeach
                </ul>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 mt-3">
                <form method="POST" action="{{ route('invoices.update', $invoice->id) }}">
                    @csrf @method('PUT')

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold">Invoice Details</h3>
                        <button class="text-xs bg-slate-900 text-white px-3 py-1.5 rounded-lg hover:bg-slate-800">
                            Update
                        </button>
                    </div>


                    <div class="space-y-3 text-sm mb-6 border-b pb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 items-center">
                            <label class="text-xs text-slate-500">Diskon (Rp)</label>
                            <input name="discount_amount" type="number"
                                value="{{ old('discount_amount', $invoice->discount_amount) }}"
                                class="w-full text-right rounded-lg border-slate-200 text-xs px-2 py-1.5">
                        </div>

                        <div class="grid grid-cols-2 gap-2 items-center">
                            <label class="text-xs text-slate-500">Pajak (%)</label>
                            <input name="tax_percent" type="number" step="0.01"
                                value="{{ old('tax_percent', $invoice->tax_percent) }}"
                                class="w-full text-right rounded-lg border-slate-200 text-xs px-2 py-1.5">
                        </div>

                        <div class="border-t pt-3 flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>


                    <div class="space-y-4 text-sm">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Judul</label>
                            <input name="judul" value="{{ old('judul', $invoice->judul) }}" required
                                class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold mb-1">Tanggal</label>
                                <input type="date" name="tgl_invoice"
                                    value="{{ old('tgl_invoice', $invoice->tgl_invoice?->format('Y-m-d')) }}" required
                                    class="w-full rounded-lg border-slate-200 px-2 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1">Jatuh Tempo</label>
                                <input type="date" name="jatuh_tempo"
                                    value="{{ old('jatuh_tempo', $invoice->jatuh_tempo?->format('Y-m-d')) }}" required
                                    class="w-full rounded-lg border-slate-200 px-2 py-2 text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Status</label>
                            <select name="status" class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm bg-white">
                                <option value="draft" {{ $invoice->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ $invoice->status == 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ $invoice->status == 'cancelled' ? 'selected' : '' }}>
                                    Cancelled
                                </option>
                            </select>
                            <div class="mt-3">
                                <label class="block text-xs font-semibold mb-1">PIC (Person In Charge)</label>
                                <select name="pic_id" class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm bg-white">
                                    <option value="">-- Pilih PIC --</option>
                                    @foreach($pics as $p)
                                        <option value="{{ $p->id }}" {{ $invoice->pic_id == $p->id ? 'selected' : '' }}>
                                            {{ $p->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mt-3">
                                <label class="block text-xs font-semibold mb-1">Payment Info</label>
                                <textarea name="payment_info" rows="4" placeholder="Bank details etc..."
                                    class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm">{{ old('payment_info', $invoice->payment_info) }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>


            </div>

        </div>
    </div>
    @push('scripts')
        <script>
            const invoiceId = {{ $invoice->id }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            async function addTerm() {
                const input = document.getElementById('term-input');
                const isi = input.value.trim();
                if (!isi) return;

                try {
                    const res = await fetch(`{{ url('/invoices') }}/${invoiceId}/terms`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            isi
                        })
                    });

                    if (!res.ok) throw new Error('Failed to add term');

                    const data = await res.json();
                    const term = data.term;

                    const noMsg = document.getElementById('no-terms-msg');
                    if (noMsg) noMsg.remove();

                    const list = document.getElementById('terms-list');
                    const li = document.createElement('li');
                    li.id = `term-row-${term.id}`;
                    li.className =
                        'group flex items-start justify-between text-sm border-b border-slate-100 pb-1 last:border-0 hover:bg-slate-50 rounded px-1 -mx-1';
                    li.innerHTML = `
                                                                                                                                                                    <span class="text-slate-700 leading-snug">• ${term.isi}</span>
                                                                                                                                                                    <button type="button" onclick="deleteTerm(${term.id})" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-1 ml-2 hidden group-hover:block">&times;</button>
                                                                                                                                                                `;
                    list.appendChild(li);
                    input.value = '';
                } catch (e) {
                    console.error(e);
                    alert('Gagal menambah term');
                }
            }

            async function deleteTerm(id) {
                if (!confirm('Hapus term ini?')) return;
                try {
                    const res = await fetch(`{{ url('/invoices') }}/${invoiceId}/terms/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    if (!res.ok) throw new Error('Failed to delete');
                    document.getElementById(`term-row-${id}`).remove();
                } catch (e) {
                    console.error(e);
                    alert('Gagal menghapus term');
                }
            }
        </script>
    @endpush
@endsection
