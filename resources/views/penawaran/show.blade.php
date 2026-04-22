@extends('layouts.app', ['title' => 'Detail Penawaran'])

@section('content')
    @php
        $docNo = $penawaran->docNumber?->doc_no ?? 'PNW-' . str_pad((string) $penawaran->id, 6, '0', STR_PAD_LEFT);
        $total = $penawaran->calcItemsSubtotal();
        $discountAmount = $penawaran->calcDiscountAmount();
        $dpp = $penawaran->calcDppTotal();
        $taxAmount = $penawaran->calcTaxAmount();
        $grandTotal = $penawaran->calcGrandTotal();
    @endphp

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-5">
        <div class="">
            <div class="text-xs text-slate-500">Nomor Dokumen</div>
            <h1 class="text-xl font-semibold">{{ $docNo }}</h1>
            <div class="text-sm text-slate-600 mt-1">{{ $penawaran->judul ?? '-' }}</div>

            @if ($penawaran->usulan)
                <div class="text-sm text-slate-600 mt-1">
                    <span class="font-medium">Diusulkan oleh:</span> {{ $penawaran->usulan->creator->name ?? '-' }}
                </div>
            @endif

            <div id="penawaran-totals">
                <div class="text-sm font-semibold text-slate-700 mt-3">
                    Total Penawaran sebelum pajak dan diskon : Rp {{ number_format((int) $total, 0, ',', '.') }}
                </div>
                <div class="text-sm font-semibold text-slate-700">
                    Total Penawaran setelah pajak dan diskon : Rp {{ number_format((int) $grandTotal, 0, ',', '.') }}
                </div>
            </div>

            @if ($penawaran->is_goal)
                <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-1.5 text-sm font-semibold text-blue-800">
                    🏆 Goal / Project
                    @if ($penawaran->goal_at)
                        <span class="text-xs font-normal text-blue-600">sejak {{ $penawaran->goal_at->format('d M Y') }}</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            @php
                $approval = $penawaran->approval ?? null;
                $stepAktif = $approval?->steps?->where('step_order', $approval->current_step)->first();

                $akses = $stepAktif->akses_approve ?? [];
                $m = $approval->module ?? '';

                // Check if user can edit: admin OR creator of penawaran
                $canEdit = auth()->user()->hasRole('admin') || $penawaran->id_user === auth()->id();
            @endphp
            @if ($bolehApproveStep && $m === 'penawaran')
                <button
                    onclick="openApprovalModal({{ $approval->id }},'{{ $approval->current_step }}','{{ $stepAktif->step_name }}')"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg">
                    Persetujuan Penawaran
                </button>
            @elseif ($bolehApproveStep && $m === 'penghapusan')
                <button
                    onclick="openApprovalModal({{ $approval->id }},'{{ $approval->current_step }}','{{ $stepAktif->step_name }}')"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg">
                    Penghapusan Penawaran
                </button>
            @endif
            <a href="{{ route('penawaran.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Kembali
            </a>
            <a href="{{ route('penawaran.pdf', $penawaran->id) }}"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Download
                PDF</a>

            <a href="{{ route('invoices.create_from_penawaran', $penawaran->id) }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Buat Invoice
            </a>

            @if ($canEdit)
                <a href="{{ route('penawaran.edit', $penawaran->id) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Edit</a>
                <form method="POST" action="{{ route('penawaran.destroy', $penawaran->id) }}"
                    onsubmit="return confirm('Hapus penawaran ini?')">
                    @csrf
                    @method('DELETE')
                    <button
                        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                </form>

                {{-- Toggle Goal / Project --}}
                <form method="POST" action="{{ route('penawaran.toggle-goal', $penawaran->id) }}"
                    onsubmit="return confirm('{{ $penawaran->is_goal ? 'Cabut status Goal / Project?' : 'Tandai penawaran ini sebagai Goal / Project?' }}')">
                    @csrf
                    @if ($penawaran->is_goal)
                        <button type="submit"
                            class="rounded-xl border border-blue-300 bg-blue-100 px-4 py-2.5 text-sm font-semibold text-blue-800 hover:bg-blue-200">
                            ✓ Goal
                        </button>
                    @else
                        <button type="submit"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                            🏆 Tandai Goal
                        </button>
                    @endif
                </form>
            @else
                <button disabled
                    class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed"
                    title="Hanya pembuat penawaran dan admin yang dapat mengedit">Edit</button>
                <button disabled
                    class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed"
                    title="Hanya pembuat penawaran dan admin yang dapat menghapus">Hapus</button>
            @endif

            @if(auth()->user()->hasPermission('create-penawaran'))
                <form method="POST" action="{{ route('penawaran.duplicate', $penawaran->id) }}"
                    onsubmit="return confirm('Duplikat penawaran ini? Salinan baru akan dibuat dengan nomor dokumen baru.')">
                    @csrf
                    <button type="submit"
                        class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        ⧉ Duplikat
                    </button>
                </form>
            @endif
        </div>


    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 relative">
                @if ($canEdit)
                    <input id="toggle_bundle" type="checkbox"
                        class="peer absolute left-5 top-6 h-4 w-4 rounded border-slate-300 accent-slate-900">
                @endif

                <div class="flex items-center justify-between gap-3 {{ $canEdit ? 'pl-7' : '' }}">
                    <div class="flex items-center gap-3">
                        @if ($canEdit)
                            <label for="toggle_bundle" class="cursor-pointer text-sm font-semibold select-none">
                                Tambah Bundle
                            </label>
                            <div class="text-xs text-slate-500 hidden md:block">Centang untuk buka form</div>
                        @else
                            <div class="text-sm font-semibold">Tambah Bundle</div>
                            <div class="text-xs text-slate-500 hidden md:block">Hanya pembuat penawaran dan admin yang
                                dapat menambah item</div>
                        @endif
                    </div>


                </div>

                @if ($canEdit)
                    <div class="mt-4 hidden peer-checked:block">
                        <form method="POST" action="{{ route('penawaran.items.bundle', $penawaran->id) }}"
                            class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            @csrf

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1">Product</label>
                                <div class="relative bundle-product-picker">
                                    <input type="text"
                                        class="bundle-product-search w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400"
                                        placeholder="Ketik nama / kode produk..."
                                        autocomplete="off">
                                    <input type="hidden" name="product_id" class="bundle-product-id">
                                    <div class="bundle-product-dropdown hidden absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                                    </div>
                                </div>
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
                @endif
            </div>




            <div class="rounded-2xl border border-slate-200 bg-white p-5 relative">
                @if ($canEdit)
                    <input id="toggle_custom" type="checkbox"
                        class="peer absolute left-5 top-6 h-4 w-4 rounded border-slate-300 accent-slate-900">
                @endif

                <div class="flex items-center justify-between gap-3 {{ $canEdit ? 'pl-7' : '' }}">
                    <div class="flex items-center gap-3">
                        @if ($canEdit)
                            <label for="toggle_custom" class="cursor-pointer text-sm font-semibold select-none">
                                Tambah Item Custom
                            </label>
                            <div class="text-xs text-slate-500 hidden md:block">Centang untuk buka form</div>
                        @endif
                    </div>
                </div>

                @if ($canEdit)
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
                @endif
            </div>



            <div class="space-y-4" id="items-wrap">
                @forelse($penawaran->items as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 pb-4 pt-3 penawaran-item" draggable="true"
                        data-item-id="{{ $item->id }}">

                        {{-- VIEW MODE --}}
                        <div class="item-view-{{ $item->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-lg font-semibold">{{ $item->judul }}</div>
                                    @if ($item->catatan)
                                        <div class="text-sm text-slate-600 mt-1">{{ $item->catatan }}</div>
                                    @endif
                                    @php
                                        $qtyBundle = $item->resolvedQty();
                                        $itemSubtotal = $item->calcSubtotal();
                                        $unitPrice = $item->calcUnitSubtotal();
                                        $itemRawSubtotal = $item->calcRawSubtotal();
                                        $itemDiscAmount = $item->calcDiscountAmount();
                                    @endphp

                                    <div class="mt-2 text-sm text-slate-600">
                                        Harga Satuan :
                                        <span class="font-semibold">Rp {{ number_format((int) $unitPrice, 0, ',', '.') }}</span>
                                        <span class="text-slate-400">•</span>
                                        Qty :
                                        <span class="font-semibold">{{ number_format($qtyBundle, 2, ',', '.') }}</span>
                                        <span class="text-slate-400">•</span>
                                        Satuan :
                                        <span class="font-semibold">{{ $item->satuan ?? 'ls' }}</span>
                                        @if (($item->markup ?? 1) != 1)
                                            <span class="text-slate-400">•</span>
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                ×{{ rtrim(rtrim(number_format((float)$item->markup, 2, '.', ''), '0'), '.') }}
                                            </span>
                                        @endif
                                        <span class="text-slate-400">•</span>
                                        Total:
                                        <span class="font-semibold">Rp {{ number_format((int) $itemSubtotal, 0, ',', '.') }}</span>
                                    </div>
                                    @if ($item->discount_enabled && $itemDiscAmount > 0)
                                        <div class="mt-1 flex items-center gap-2 text-xs">
                                            <span class="text-slate-400 line-through">Rp {{ number_format($itemRawSubtotal, 0, ',', '.') }}</span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                                Diskon
                                                @if (($item->discount_type ?? 'percent') === 'percent')
                                                    {{ number_format((float) $item->discount_value, 0, ',', '.') }}%
                                                @else
                                                    Rp {{ number_format((int) $item->discount_value, 0, ',', '.') }}
                                                @endif
                                                &minus; Rp {{ number_format($itemDiscAmount, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($canEdit)
                                        <button type="button"
                                            onclick="itemInlineEdit({{ $item->id }})"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                            Edit
                                        </button>
                                    @endif
                                    <button type="button"
                                        data-delete-url="{{ route('penawaran.items.delete', [$penawaran->id, $item->id]) }}"
                                        data-confirm="Hapus item ini?"
                                        class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        Hapus Item
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- EDIT MODE --}}
                        <div class="item-edit-{{ $item->id }} hidden">
                            <form method="POST" id="item-form-{{ $item->id }}"
                                action="{{ route('penawaran.items.update', [$penawaran->id, $item->id]) }}">
                                @csrf
                                @method('PUT')

                                <div class="flex items-start gap-3">
                                    <div class="flex-1 space-y-2">
                                        {{-- Judul --}}
                                        <input name="judul" value="{{ $item->judul }}"
                                            placeholder="Judul{{ $item->tipe === 'bundle' ? ' Bundle' : ' Item' }}"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-base font-semibold focus:outline-none focus:ring-1 focus:ring-slate-400">

                                        <input name="catatan" value="{{ $item->catatan }}"
                                            placeholder="Catatan (opsional)"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 focus:outline-none focus:ring-1 focus:ring-slate-400">

                                        <div class="flex gap-2">
                                            <div class="flex-1">
                                                <label class="block text-xs font-semibold mb-1 text-slate-500">Qty</label>
                                                <input name="qty" value="{{ $item->qty ?? 1 }}" inputmode="decimal"
                                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-xs font-semibold mb-1 text-slate-500">Satuan</label>
                                                <input name="satuan" value="{{ $item->satuan ?? 'ls' }}"
                                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">
                                            </div>
                                            <div class="w-24">
                                                <label class="block text-xs font-semibold mb-1 text-amber-600">Markup ×</label>
                                                <input name="markup" value="{{ $item->markup ?? '1.00' }}" inputmode="decimal" step="0.01"
                                                    class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800 focus:outline-none focus:ring-1 focus:ring-amber-400"
                                                    placeholder="1.00">
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 relative">
                                            <input id="item_disc_{{ $item->id }}" type="checkbox"
                                                name="discount_enabled" value="1"
                                                class="peer absolute left-3 top-3.5 h-4 w-4 rounded border-slate-300 accent-slate-900"
                                                {{ $item->discount_enabled ? 'checked' : '' }}>
                                            <div class="pl-7">
                                                <label for="item_disc_{{ $item->id }}"
                                                    class="cursor-pointer text-xs font-semibold select-none">
                                                    Aktifkan Diskon Item
                                                </label>
                                            </div>
                                            <div class="mt-2 hidden peer-checked:flex gap-2 pl-2">
                                                <div class="flex-1">
                                                    <label class="block text-xs font-semibold mb-1">Tipe</label>
                                                    <select name="discount_type"
                                                        class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                                        <option value="percent" {{ ($item->discount_type ?? 'percent') === 'percent' ? 'selected' : '' }}>%</option>
                                                        <option value="fixed" {{ ($item->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Rp</option>
                                                    </select>
                                                </div>
                                                <div class="flex-1">
                                                    <label class="block text-xs font-semibold mb-1">Nilai</label>
                                                    <input name="discount_value"
                                                        value="{{ $item->discount_value ?? 0 }}"
                                                        inputmode="decimal"
                                                        class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1.5 shrink-0 pt-1">
                                        <button type="submit"
                                            class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                            Simpan
                                        </button>
                                        <button type="button"
                                            onclick="itemInlineCancel({{ $item->id }})"
                                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                                            Batal
                                        </button>
                                    </div>
                                </div>
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
                                <tbody class="border border-slate-200 divide-y divide-slate-100 detail-sortable"
                                    data-item-id="{{ $item->id }}">
                                    @forelse($item->details as $d)
                                        @php $detailSubtotal = $d->calcSubtotal(); @endphp
                                        {{-- VIEW ROW --}}
                                        <tr class="detail-row detail-view-{{ $d->id }}" draggable="true" data-detail-id="{{ $d->id }}">
                                            <td class="px-3 py-2 text-slate-500 text-xs">{{ $d->urutan }}</td>
                                            <td class="px-3 py-2">
                                                <div class="font-semibold">{{ $d->nama }}</div>
                                                @if ($d->spesifikasi)
                                                    <div class="text-xs text-slate-500 mt-0.5">{{ $d->spesifikasi }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format((float) $d->qty, 2, ',', '.') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $d->satuan }}</td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">Rp {{ number_format((int) $d->harga, 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">
                                                Rp {{ number_format($detailSubtotal, 0, ',', '.') }}
                                                @if (($d->markup ?? 1) != 1)
                                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-1 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 ml-1">
                                                        ×{{ rtrim(rtrim(number_format((float)$d->markup, 2, '.', ''), '0'), '.') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                @if ($canEdit)
                                                <button type="button"
                                                    onclick="detailInlineEdit({{ $d->id }})"
                                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                                    Edit
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        {{-- EDIT ROW --}}
                                        <tr class="detail-edit-{{ $d->id }} hidden bg-slate-50">
                                            <td class="px-2 py-2 text-slate-400 text-xs align-top pt-3">{{ $d->urutan }}</td>
                                            <td class="px-2 py-1.5" colspan="4">
                                                <form method="POST"
                                                    id="detail-form-{{ $d->id }}"
                                                    action="{{ route('penawaran.item_details.update', [$penawaran->id, $item->id, $d->id]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="flex flex-col gap-1.5">
                                                        <input name="nama" value="{{ $d->nama }}"
                                                            placeholder="Nama"
                                                            class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">
                                                        <input name="spesifikasi" value="{{ $d->spesifikasi }}"
                                                            placeholder="Spesifikasi (opsional)"
                                                            class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-1 focus:ring-slate-400">
                                                        <div class="flex gap-1.5">
                                                            <input name="qty" value="{{ $d->qty }}"
                                                                placeholder="Qty"
                                                                class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">
                                                            <input name="satuan" value="{{ $d->satuan }}"
                                                                placeholder="Satuan"
                                                                class="w-24 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">
                                                            <input name="harga" value="{{ $d->harga }}"
                                                                placeholder="Harga"
                                                                inputmode="numeric"
                                                                class="flex-1 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm rupiah-input focus:outline-none focus:ring-1 focus:ring-slate-400">
                                                            <input name="markup" value="{{ $d->markup ?? '1.00' }}"
                                                                placeholder="×1.00"
                                                                inputmode="decimal" step="0.01"
                                                                title="Markup"
                                                                class="w-16 rounded-lg border border-amber-300 bg-amber-50 px-2 py-1.5 text-sm font-semibold text-amber-800 focus:outline-none focus:ring-1 focus:ring-amber-400">
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="px-2 py-1.5 text-right font-semibold text-slate-400 whitespace-nowrap align-top pt-3">
                                                Rp {{ number_format($detailSubtotal, 0, ',', '.') }}
                                            </td>
                                            <td class="px-2 py-1.5 align-top whitespace-nowrap">
                                                <div class="flex flex-col gap-1 items-end">
                                                    <button type="submit" form="detail-form-{{ $d->id }}"
                                                        class="w-full rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                                                        Simpan
                                                    </button>
                                                    <button type="button"
                                                        onclick="detailInlineCancel({{ $d->id }})"
                                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">
                                                        Batal
                                                    </button>
                                                    <button type="button"
                                                        data-delete-url="{{ route('penawaran.item_details.delete', [$penawaran->id, $item->id, $d->id]) }}"
                                                        data-confirm="Hapus detail ini?"
                                                        class="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">Belum ada rincian.</td>
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
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold mb-1">Cari Komponen</label>
                                        <div class="relative komponen-picker" data-item-id="{{ $item->id }}">
                                            <input type="text" placeholder="Ketik untuk cari komponen..."
                                                class="komponen-search w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                            <div
                                                class="komponen-dropdown hidden absolute z-20 mt-1 w-full max-h-56 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                            </div>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-1">Pilih komponen untuk auto isi nama,
                                            spesifikasi, satuan, dan harga.</div>
                                    </div>
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
                                        <input name="harga" value="0" inputmode="numeric"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm rupiah-input">
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


                @if ($canEdit)
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
                @endif

                <div class="mt-4 space-y-2 term-list" data-parent-id="">
                    @php
                        $termsByParent = $penawaran->terms->groupBy('parent_id');
                        $roots = $termsByParent[null] ?? collect();
                    @endphp
                    @forelse ($roots as $t)
                        @include('penawaran.partials.term_node', [
                            'penawaran' => $penawaran,
                            'term' => $t,
                            'termsByParent' => $termsByParent,
                            'canEdit' => $canEdit,
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
                <form method="POST" action="{{ route('penawaran.pricing.upsert', $penawaran->id) }}" class="">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between mb-3">
                        <div class="font-semibold">Diskon & Pajak</div>
                        <button
                            class="rounded-xl m-0 bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Simpan
                        </button>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 relative">
                        <input id="discount_enabled" type="checkbox" name="discount_enabled" value="1"
                            class="peer absolute left-4 top-4 h-4 w-4 rounded border-slate-300 accent-slate-900"
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


                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 relative mt-3">
                        <input id="tax_enabled" type="checkbox" name="tax_enabled" value="1"
                            class="peer absolute left-4 top-4 h-4 w-4 rounded border-slate-300 accent-slate-900"
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
                @php
                    $signature = $penawaran->signatures->first();
                @endphp
                @if ($canEdit)
                    <form method="POST" action="{{ route('penawaran.signatures.add', $penawaran->id) }}"
                        enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold mb-1">Nama</label>
                            <input name="nama" value="{{ old('nama', $signature->nama ?? '') }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nama"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Jabatan</label>
                            <input name="jabatan" value="{{ old('jabatan', $signature->jabatan ?? '') }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Jabatan">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold mb-1">Kota</label>
                                <input name="kota" value="{{ old('kota', $signature->kota ?? 'Sleman') }}"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    placeholder="Kota">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1">Tanggal</label>
                                <input type="date" name="tanggal"
                                    value="{{ old('tanggal', $signature->tanggal ?? now()->toDateString()) }}"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            </div>
                        </div>

                        @if ($signature && $signature->ttd_path)
                            <div>
                                <label class="block text-xs font-semibold mb-1">Tanda Tangan Saat Ini</label>
                                <img src="{{ asset('storage/' . $signature->ttd_path) }}" alt="TTD"
                                    class="h-24 border border-slate-200 rounded-lg p-2 bg-slate-50 mb-2">
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold mb-1">
                                {{ $signature && $signature->ttd_path ? 'Upload Tanda Tangan Baru (Opsional)' : 'Upload Tanda Tangan' }}
                            </label>
                            <input type="file" name="ttd" accept="image/*"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                            <p class="text-xs text-slate-500 mt-1">Format: JPG, PNG. Maks: 2MB.</p>
                        </div>

                        <button
                            class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ $signature ? 'Update' : 'Simpan' }} Tanda Tangan
                        </button>
                    </form>
                @else
                    <div class="text-sm text-slate-600">
                        <p class="mb-2"><strong>Nama:</strong> {{ $signature->nama ?? '-' }}</p>
                        <p class="mb-2"><strong>Jabatan:</strong> {{ $signature->jabatan ?? '-' }}</p>
                        <p class="mb-2"><strong>Kota:</strong> {{ $signature->kota ?? '-' }}</p>
                        <p class="mb-2"><strong>Tanggal:</strong> {{ $signature->tanggal ?? '-' }}</p>
                        @if ($signature && $signature->ttd_path)
                            <div class="mt-3">
                                <p class="font-semibold mb-1">Tanda Tangan:</p>
                                <img src="{{ asset('storage/' . $signature->ttd_path) }}" alt="TTD"
                                    class="h-24 border border-slate-200 rounded-lg p-2 bg-slate-50">
                            </div>
                        @endif
                    </div>
                @endif
            </div>



            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold mb-3">Lampiran</h2>
                @if ($canEdit)
                    <form method="POST" action="{{ route('penawaran.attachments.add', $penawaran->id) }}"
                        enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <div id="penawaran-attachment-container" class="space-y-2">
                            <div class="flex gap-2 attachment-row">
                                {{-- <input name="judul[]"
                                    class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    placeholder="Judul (opsional)"> --}}
                                <input type="file" name="files[]" accept="application/pdf"
                                    class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                            </div>
                        </div>
                        <button type="button" onclick="addPenawaranAttachmentRow()"
                            class="text-sm text-blue-600 hover:underline">+ Tambah lampiran</button>
                        <button
                            class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Upload</button>
                    </form>
                @endif

                <div class="mt-3 space-y-2">
                    @foreach ($penawaran->attachments as $a)
                        <div class="rounded-2xl border border-slate-200 p-4 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold truncate">{{ $a->judul ?? 'Lampiran' }}</div>
                                <div class="text-xs text-slate-500 truncate">{{ $a->file_path }}</div>
                            </div>
                            @if ($canEdit)
                                <form method="POST"
                                    action="{{ route('penawaran.attachments.delete', [$penawaran->id, $a->id]) }}"
                                    onsubmit="return confirm('Hapus lampiran?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- modal untuk approval --}}
    <div id="approvalModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        onclick="closeApprovalModal(event)">

        <div class="bg-white w-full max-w-md rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-2">Proses Approval</h2>

            <div class="mb-4 p-3 bg-slate-100 rounded-lg text-sm">
                <div>Langkah Saat Ini :</div>
                <div class="font-semibold text-slate-800">
                    Step <span id="modal_step_order"></span> —
                    <span id="modal_step_name"></span>
                </div>
            </div>

            <form method="POST" action="{{ route('approval.process') }}">
                @csrf
                <input type="hidden" name="approval_id" id="modal_approval_id">

                <textarea name="catatan" class="w-full border rounded-lg p-2 mb-4" placeholder="Catatan (opsional)"></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeApprovalModal()"
                        class="px-4 py-2 rounded-lg border">Batal</button>

                    <button name="aksi" value="reject"
                        class="bg-red-500 text-white px-4 py-2 rounded-lg">Tolak</button>

                    <button name="aksi" value="approve"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg">Setujui</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openApprovalModal(id, stepOrder, stepName) {
            document.getElementById('modal_approval_id').value = id;
            document.getElementById('modal_step_order').innerText = stepOrder;
            document.getElementById('modal_step_name').innerText = stepName ?? '-';
            document.getElementById('approvalModal').classList.remove('hidden');
        }

        function closeApprovalModal(e) {
            if (!e || e.target.id === 'approvalModal') {
                document.getElementById('approvalModal').classList.add('hidden');
            }
        }

        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-xl shadow-lg font-semibold text-white transition-all duration-300 ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Refresh page content without full reload
        async function refreshContent() {
            console.log('🔄 Refreshing page content...');
            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Find the main content container and replace it
                const newContent = doc.querySelector('main') || doc.querySelector('.container') || doc.querySelector(
                    '[class*="max-w"]');
                const currentContent = document.querySelector('main') || document.querySelector('.container') ||
                    document.querySelector('[class*="max-w"]');

                if (newContent && currentContent) {
                    currentContent.innerHTML = newContent.innerHTML;
                    console.log('✅ Content refreshed!');
                    initDetailDragDrop(currentContent);
                } else {
                    // Fallback: reload the whole page
                    console.log('⚠️ Could not find content container, reloading...');
                    window.location.reload();
                }
            } catch (error) {
                console.error('❌ Error refreshing content:', error);
                window.location.reload();
            }
        }

        // Refresh only bundle items list
        async function refreshItems() {
            console.log('🔄 Refreshing items...');
            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newItems = doc.querySelector('#items-wrap');
                const currentItems = document.querySelector('#items-wrap');
                const newTotals = doc.querySelector('#penawaran-totals');
                const currentTotals = document.querySelector('#penawaran-totals');

                if (newItems && currentItems) {
                    currentItems.innerHTML = newItems.innerHTML;
                    console.log('✅ Items refreshed!');
                    initKomponenPickers(currentItems);
                    initRupiahInputs(currentItems);
                    initTermDragDrop(currentItems);
                    initItemDragDrop(currentItems);
                    initDetailDragDrop(currentItems);
                }

                if (newTotals && currentTotals) {
                    currentTotals.innerHTML = newTotals.innerHTML;
                }

                if (!newItems || !currentItems) {
                    console.log('⚠️ Items container not found, fallback to full refresh...');
                    await refreshContent();
                }
            } catch (error) {
                console.error('❌ Error refreshing items:', error);
                await refreshContent();
            }
        }

        // AJAX Form Handler
        async function handleAjaxSubmit(form) {
            console.log('🚀 AJAX submit for:', form.action);

            normalizeRupiahInputs(form);
            const formData = new FormData(form);
            const url = form.action;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                let data = null;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    data = {
                        message: text || null
                    };
                }
                console.log('📥 Response:', data);

                if (response.ok) {
                    showToast(data?.message || 'Berhasil!', 'success');
                    console.log(response);
                    form.reset();
                    const methodInput = form.querySelector('input[name="_method"]');
                    const method = methodInput && methodInput.value ? methodInput.value.toUpperCase() : 'POST';
                    if (form.action.includes('/items/') || form.action.includes('/item_details/')) {
                        await refreshItems();
                    } else {
                        await refreshContent();
                    }
                } else {
                    showToast(data?.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                showToast('Terjadi kesalahan koneksi', 'error');
            }
        }

        // Forms to EXCLUDE from AJAX (let them reload normally)
        function shouldSkipAjax(formAction) {
            if (!formAction) return true;
            // Skip signatures - they need page reload
            if (formAction.includes('signatures')) return true;
            // Skip main penawaran destroy (the delete entire penawaran)
            if (formAction.match(/\/penawaran\/\d+$/) && formAction.includes('destroy')) return true;
            // Skip approval form
            if (formAction.includes('approval')) return true;
            // Skip duplicate - it redirects to new penawaran page
            if (formAction.includes('duplicate')) return true;
            return false;
        }

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            const input = document.querySelector('input[name="_token"]');
            return input ? input.value : '';
        }

        function ajaxDelete(url, confirmText) {
            if (confirmText && !confirm(confirmText)) return;
            const form = document.createElement('form');
            form.action = url;
            const token = getCsrfToken();
            if (token) {
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = token;
                form.appendChild(csrf);
            }
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);
            handleAjaxSubmit(form);
        }

        async function saveTermOrder(listEl) {
            const parentId = listEl.dataset.parentId || '';
            const ids = Array.from(listEl.querySelectorAll(':scope > .term-node'))
                .map(el => parseInt(el.dataset.termId, 10))
                .filter(Boolean);
            if (!ids.length) return;

            const payload = {
                parent_id: parentId === '' ? null : parseInt(parentId, 10),
                ids
            };

            try {
                const res = await fetch('{{ route('penawaran.terms.reorder', $penawaran->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showToast(data.message || 'Gagal memperbarui urutan', 'error');
                }
            } catch (e) {
                console.error('Reorder error:', e);
                showToast('Terjadi kesalahan koneksi', 'error');
            }
        }

        async function saveItemOrder() {
            const wrap = document.getElementById('items-wrap');
            if (!wrap) return;
            const ids = Array.from(wrap.querySelectorAll(':scope > .penawaran-item'))
                .map(el => parseInt(el.dataset.itemId, 10))
                .filter(Boolean);
            if (!ids.length) return;

            try {
                const res = await fetch('{{ route('penawaran.items.reorder', $penawaran->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        ids
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showToast(data.message || 'Gagal memperbarui urutan', 'error');
                }
            } catch (e) {
                console.error('Reorder item error:', e);
                showToast('Terjadi kesalahan koneksi', 'error');
            }
        }

        async function saveDetailOrder(listEl) {
            const itemId = listEl.dataset.itemId;
            if (!itemId) return;
            const ids = Array.from(listEl.querySelectorAll(':scope > .detail-row'))
                .map(el => parseInt(el.dataset.detailId, 10))
                .filter(Boolean);
            if (!ids.length) return;

            try {
                const res = await fetch(
                    `{{ url('/penawaran') }}/{{ $penawaran->id }}/items/${itemId}/details/reorder`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify({
                            ids
                        })
                    });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showToast(data.message || 'Gagal memperbarui urutan rincian', 'error');
                } else {
                    await refreshItems();
                }
            } catch (e) {
                console.error('Reorder detail error:', e);
                showToast('Terjadi kesalahan koneksi', 'error');
            }
        }

        function initDetailDragDrop(root = document) {
            const isInteractive = (target) => !!target.closest(
                'input, textarea, select, button, a, label, summary, details');
            root.querySelectorAll('.detail-row').forEach(row => {
                row.addEventListener('dragstart', (e) => {
                    if (isInteractive(e.target)) {
                        e.preventDefault();
                        return;
                    }
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.dataset.detailId || '');
                    row.classList.add('opacity-50');
                });
                row.addEventListener('dragend', () => {
                    row.classList.remove('opacity-50');
                });
            });

            root.querySelectorAll('.detail-sortable').forEach(list => {
                list.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const dragging = list.querySelector('.detail-row.opacity-50');
                    if (!dragging) return;
                    const after = Array.from(list.querySelectorAll(':scope > .detail-row'))
                        .find(el => {
                            const rect = el.getBoundingClientRect();
                            return e.clientY < rect.top + rect.height / 2;
                        });
                    if (after) {
                        list.insertBefore(dragging, after);
                    } else {
                        list.appendChild(dragging);
                    }
                });
                list.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    await saveDetailOrder(list);
                });
            });
        }

        function initItemDragDrop(root = document) {
            const isInteractive = (target) => !!target.closest(
                'input, textarea, select, button, a, label, summary, details');
            const wrap = root.querySelector('#items-wrap');
            if (!wrap) return;

            wrap.querySelectorAll('.penawaran-item').forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    if (isInteractive(e.target)) {
                        e.preventDefault();
                        return;
                    }
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', item.dataset.itemId || '');
                    item.classList.add('opacity-50');
                });
                item.addEventListener('dragend', () => {
                    item.classList.remove('opacity-50');
                });
            });

            wrap.addEventListener('dragover', (e) => {
                e.preventDefault();
                const dragging = wrap.querySelector('.penawaran-item.opacity-50');
                if (!dragging) return;
                const after = Array.from(wrap.querySelectorAll(':scope > .penawaran-item'))
                    .find(el => {
                        const rect = el.getBoundingClientRect();
                        return e.clientY < rect.top + rect.height / 2;
                    });
                if (after) {
                    wrap.insertBefore(dragging, after);
                } else {
                    wrap.appendChild(dragging);
                }
            });

            wrap.addEventListener('drop', async (e) => {
                e.preventDefault();
                await saveItemOrder();
            });
        }

        function initTermDragDrop(root = document) {
            root.querySelectorAll('.term-node').forEach(node => {
                node.addEventListener('dragstart', (e) => {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', node.dataset.termId || '');
                    node.classList.add('opacity-50');
                });
                node.addEventListener('dragend', () => {
                    node.classList.remove('opacity-50');
                });
            });

            root.querySelectorAll('.term-list').forEach(list => {
                list.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const dragging = root.querySelector('.term-node.opacity-50');
                    if (!dragging) return;
                    const after = Array.from(list.querySelectorAll(':scope > .term-node'))
                        .find(el => {
                            const rect = el.getBoundingClientRect();
                            return e.clientY < rect.top + rect.height / 2;
                        });
                    if (after) {
                        list.insertBefore(dragging, after);
                    } else {
                        list.appendChild(dragging);
                    }
                });
                list.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    await saveTermOrder(list);
                });
            });
        }

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-delete-url]');
            if (!btn) return;
            e.preventDefault();
            ajaxDelete(btn.dataset.deleteUrl, btn.dataset.confirm || '');
        });

        function addPenawaranAttachmentRow() {
            const container = document.getElementById('penawaran-attachment-container');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'flex gap-2 attachment-row';
            row.innerHTML = `
                                <input name="judul[]" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Judul (opsional)">
                                <input type="file" name="files[]" accept="application/pdf" class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-sm">Hapus</button>
                            `;
            container.appendChild(row);
        }


        document.addEventListener('submit', function(e) {
            const form = e.target;

            if (form.tagName !== 'FORM') return;

            console.log('📋 Form submit detected:', form.action);
            normalizeRupiahInputs(form);
            if (shouldSkipAjax(form.action)) {
                console.log('⏭ Skipping AJAX for this form');
                return;
            }
            console.log('✅ Using AJAX');
            e.preventDefault();
            e.stopPropagation();
            const methodInput = form.querySelector('input[name="_method"]');
            const isDeleteMethod = methodInput && methodInput.value && methodInput.value.toUpperCase() === 'DELETE';
            if (form.action.includes('delete') || form.action.includes('destroy') || isDeleteMethod) {
                if (!confirm('Hapus item ini?')) {
                    return;
                }
            }

            handleAjaxSubmit(form);
        }, true);

        console.log('✅ AJAX event delegation ready!');
    </script>

    <script src="https://cdn.jsdelivr.net/npm/fuse.js@7.0.0/dist/fuse.min.js"></script>
    <script>
        let komponenCache = null;

        function formatRupiahDigits(digits) {
            if (!digits) return '';
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function bindRupiahInput(input) {
            if (!input || input.dataset.rupiahBound) return;
            input.dataset.rupiahBound = '1';
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '');
                input.value = formatRupiahDigits(digits);
            });
            const initial = input.value.replace(/\D/g, '');
            input.value = formatRupiahDigits(initial);
        }

        function initRupiahInputs(root = document) {
            root.querySelectorAll('input.rupiah-input').forEach(bindRupiahInput);
        }

        function normalizeRupiahInputs(form) {
            if (!form) return;
            form.querySelectorAll('input.rupiah-input').forEach(input => {
                input.value = input.value.replace(/\D/g, '');
            });
        }

        async function loadKomponen() {
            if (komponenCache) return komponenCache;
            try {
                const res = await fetch('{{ route('api.komponen.list') }}', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Failed to load komponen');
                komponenCache = await res.json();
                return komponenCache;
            } catch (e) {
                console.error('Failed to load komponen:', e);
                komponenCache = [];
                return komponenCache;
            }
        }

        function renderKomponenDropdown(dropdown, items) {
            dropdown.innerHTML = '';
            if (!items.length) {
                dropdown.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">Tidak ada hasil</div>';
                return;
            }

            items.forEach(k => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'w-full text-left px-3 py-2 text-sm hover:bg-slate-50';
                row.dataset.id = k.id;
                row.dataset.nama = k.nama || '';
                row.dataset.spesifikasi = k.spesifikasi || '';
                row.dataset.satuan = k.satuan || '';
                row.dataset.harga = k.harga || 0;
                row.innerHTML = `
                    <div class="font-semibold">${k.nama || '-'}</div>
                    <div class="text-xs text-slate-500">${k.kode || ''} ${k.satuan ? '• ' + k.satuan : ''} ${k.harga ? '• Rp ' + Number(k.harga).toLocaleString('id-ID') : ''}</div>
                `;
                dropdown.appendChild(row);
            });
        }

        function attachKomponenPicker(container) {
            const searchInput = container.querySelector('.komponen-search');
            const dropdown = container.querySelector('.komponen-dropdown');
            if (!searchInput || !dropdown) return;

            let localData = [];

            searchInput.addEventListener('focus', async () => {
                localData = await loadKomponen();
                dropdown.classList.remove('hidden');
                renderKomponenDropdown(dropdown, localData.slice(0, 20));
            });

            searchInput.addEventListener('input', async () => {
                const q = searchInput.value.toLowerCase().trim();
                if (!localData.length) localData = await loadKomponen();
                const filtered = localData.filter(k =>
                    (k.nama || '').toLowerCase().includes(q) ||
                    (k.kode || '').toLowerCase().includes(q)
                );
                dropdown.classList.remove('hidden');
                renderKomponenDropdown(dropdown, filtered.slice(0, 30));
            });

            dropdown.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-id]');
                if (!btn) return;

                const form = container.closest('form');
                if (!form) return;

                const nama = form.querySelector('input[name="nama"]');
                const spesifikasi = form.querySelector('input[name="spesifikasi"]');
                const satuan = form.querySelector('input[name="satuan"]');
                const harga = form.querySelector('input[name="harga"]');

                if (nama) nama.value = btn.dataset.nama || '';
                if (spesifikasi) spesifikasi.value = btn.dataset.spesifikasi || '';
                if (satuan) satuan.value = btn.dataset.satuan || '';
                if (harga) {
                    const digits = String(btn.dataset.harga || 0).replace(/\D/g, '');
                    harga.value = formatRupiahDigits(digits);
                }

                searchInput.value = btn.dataset.nama || '';
                dropdown.classList.add('hidden');
            });
        }

        function initKomponenPickers(root = document) {
            root.querySelectorAll('.komponen-picker').forEach(attachKomponenPicker);
        }

        document.addEventListener('click', (e) => {
            document.querySelectorAll('.komponen-picker').forEach(picker => {
                if (!picker.contains(e.target)) {
                    const dd = picker.querySelector('.komponen-dropdown');
                    if (dd) dd.classList.add('hidden');
                }
            });
            // close bundle product dropdown jika klik di luar
            document.querySelectorAll('.bundle-product-picker').forEach(picker => {
                if (!picker.contains(e.target)) {
                    const dd = picker.querySelector('.bundle-product-dropdown');
                    if (dd) dd.classList.add('hidden');
                }
            });
        });

        // Data produk untuk bundle picker
        const bundleProductData = @json($products->map(fn($p) => ['id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama]));

        function initBundleProductPicker(root = document) {
            root.querySelectorAll('.bundle-product-picker').forEach(picker => {
                if (picker.dataset.pickerBound) return;
                picker.dataset.pickerBound = '1';

                const searchInput = picker.querySelector('.bundle-product-search');
                const hiddenInput = picker.querySelector('.bundle-product-id');
                const dropdown   = picker.querySelector('.bundle-product-dropdown');

                // Fuse.js instance — fuzzy search pada nama + kode
                const fuse = new Fuse(bundleProductData, {
                    keys: [
                        { name: 'nama', weight: 0.7 },
                        { name: 'kode', weight: 0.3 },
                    ],
                    threshold: 0.4,       // 0 = exact, 1 = match anything
                    distance: 200,        // toleran karakter jauh di string panjang
                    includeScore: true,
                    useExtendedSearch: false,
                    ignoreLocation: true, // tidak peduli posisi match
                    minMatchCharLength: 1,
                });

                function renderOptions(q) {
                    let results;
                    if (q.trim() === '') {
                        // kosong → tampilkan semua
                        results = bundleProductData;
                    } else {
                        results = fuse.search(q).map(r => r.item);
                    }

                    if (!results.length) {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">Tidak ditemukan</div>';
                    } else {
                        dropdown.innerHTML = results.map(p => `
                            <div class="px-3 py-2 text-sm hover:bg-slate-100 cursor-pointer"
                                data-id="${p.id}"
                                data-label="${p.kode ? '['+p.kode+'] ' : ''}${p.nama}">
                                ${p.kode ? '<span class="text-xs font-mono text-slate-400 mr-1">['+p.kode+']</span>' : ''}
                                ${p.nama}
                            </div>
                        `).join('');

                        dropdown.querySelectorAll('[data-id]').forEach(item => {
                            item.addEventListener('mousedown', (e) => {
                                e.preventDefault();
                                hiddenInput.value = item.dataset.id;
                                searchInput.value = item.dataset.label;
                                dropdown.classList.add('hidden');
                            });
                        });
                    }
                    dropdown.classList.remove('hidden');
                }

                searchInput.addEventListener('focus', () => renderOptions(searchInput.value));
                searchInput.addEventListener('input', () => {
                    hiddenInput.value = '';
                    renderOptions(searchInput.value);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initKomponenPickers();
            initBundleProductPicker();
            initRupiahInputs();
            initTermDragDrop();
            initItemDragDrop();
            initDetailDragDrop();
        });

        // Re-init after AJAX refresh
        const originalRefreshContent = refreshContent;
        refreshContent = async function() {
            await originalRefreshContent();
            initKomponenPickers();
            initBundleProductPicker();
            initRupiahInputs();
            initTermDragDrop();
            initItemDragDrop();
            initDetailDragDrop();
        };

        // Inline edit untuk baris detail bundle
        function detailInlineEdit(id) {
            document.querySelectorAll('.detail-view-' + id).forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.detail-edit-' + id).forEach(el => el.classList.remove('hidden'));
            // focus input nama
            const form = document.getElementById('detail-form-' + id);
            if (form) {
                const namaInput = form.querySelector('input[name="nama"]');
                if (namaInput) namaInput.focus();
                // re-init rupiah inputs in this form
                initRupiahInputs(form);
            }
        }

        function detailInlineCancel(id) {
            document.querySelectorAll('.detail-edit-' + id).forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.detail-view-' + id).forEach(el => el.classList.remove('hidden'));
        }

        // Inline edit untuk header item (bundle / custom)
        function itemInlineEdit(id) {
            document.querySelectorAll('.item-view-' + id).forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.item-edit-' + id).forEach(el => el.classList.remove('hidden'));
            const form = document.getElementById('item-form-' + id);
            if (form) {
                const judulInput = form.querySelector('input[name="judul"]');
                if (judulInput) judulInput.focus();
            }
        }

        function itemInlineCancel(id) {
            document.querySelectorAll('.item-edit-' + id).forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.item-view-' + id).forEach(el => el.classList.remove('hidden'));
        }

        // Inline edit untuk keterangan (term)
        function termInlineEdit(id) {
            document.querySelectorAll('.term-view-' + id).forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.term-edit-' + id).forEach(el => el.classList.remove('hidden'));
            const form = document.getElementById('term-form-' + id);
            if (form) {
                const ta = form.querySelector('textarea[name="isi"]');
                if (ta) { ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); }
            }
        }

        function termInlineCancel(id) {
            document.querySelectorAll('.term-edit-' + id).forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.term-view-' + id).forEach(el => el.classList.remove('hidden'));
        }
    </script>



@endsection
