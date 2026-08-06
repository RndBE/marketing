@php
    $points = collect($points ?? [])->map(fn ($point) => trim((string) $point))->filter()->values();
@endphp

<div class="mb-3 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-3 item-row sm:mb-3 sm:grid-cols-12"
    data-item-index="{{ $index }}">
    <div class="sm:col-span-4">
        <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Item</span>
        <input type="text" name="item_judul[{{ $index }}]" value="{{ $judul ?? '' }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nama item">
        <input type="hidden" name="item_tipe[{{ $index }}]" value="{{ $tipe ?? 'custom' }}">
        <input type="hidden" name="item_product_id[{{ $index }}]" value="{{ $productId ?? '' }}">
    </div>
    <div class="sm:col-span-2">
        <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Qty</span>
        <input type="number" name="item_qty[{{ $index }}]" value="{{ $qty ?? 1 }}" step="0.01" min="0.01"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
    </div>
    <div class="sm:col-span-2">
        <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Satuan</span>
        <input type="text" name="item_satuan[{{ $index }}]" value="{{ $satuan ?? '' }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Paket/Unit">
    </div>
    <div class="sm:col-span-3">
        <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Estimasi harga</span>
        <input type="number" name="item_harga[{{ $index }}]" value="{{ $harga ?? 0 }}" min="0"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
    </div>
    <div class="flex items-center justify-end sm:col-span-1">
        <button type="button" onclick="this.closest('.item-row').remove()" class="text-sm text-red-500">Hapus</button>
    </div>

    <div class="rounded-xl bg-slate-50 p-3 sm:col-span-12">
        <div class="mb-2 flex items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold text-slate-700">Detail / Spesifikasi per Poin</div>
                <div class="text-[11px] text-slate-500">Tambahkan poin kebutuhan khusus untuk item ini.</div>
            </div>
            <button type="button" onclick="addItemPoint(this)"
                class="shrink-0 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                + Tambah Poin
            </button>
        </div>
        <div class="item-point-container space-y-2">
            @foreach ($points as $pointIndex => $point)
                <div class="item-point-row flex items-center gap-2">
                    <span class="item-point-label w-12 shrink-0 text-xs font-semibold text-slate-500">Poin {{ $pointIndex + 1 }}</span>
                    <input type="text" name="item_poin[{{ $index }}][]" value="{{ $point }}"
                        class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                        placeholder="Contoh: Material stainless steel 304">
                    <button type="button" onclick="removeItemPoint(this)" class="text-xs font-semibold text-red-500">Hapus</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
