@extends('layouts.app')

@section('content')
    <div class="w-full max-w-3xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Edit Usulan Penawaran</h1>
        </div>

        <form method="POST" action="{{ route('usulan.update', $usulan->id) }}" enctype="multipart/form-data"
            class="space-y-4">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Judul Prospek *</label>
                    <input type="text" name="judul" value="{{ old('judul', $usulan->judul) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1">PIC/Klien</label>
                        <select name="pic_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">-- Pilih PIC --</option>
                            @foreach ($pics as $pic)
                                <option value="{{ $pic->id }}" {{ $usulan->pic_id == $pic->id ? 'selected' : '' }}>
                                    {{ $pic->instansi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Nilai Estimasi</label>
                        <input type="number" name="nilai_estimasi"
                            value="{{ old('nilai_estimasi', $usulan->nilai_estimasi) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" min="0">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Tanggal Dibutuhkan</label>
                    <input type="date" name="tanggal_dibutuhkan"
                        value="{{ old('tanggal_dibutuhkan', $usulan->tanggal_dibutuhkan?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Jelaskan detail prospek, kebutuhan klien, dll...">{{ old('deskripsi', $usulan->deskripsi) }}</textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-semibold">Item Usulan (Opsional)</label>
                    <button type="button" onclick="addItemRow()" class="text-sm text-blue-600 hover:underline">+
                        Tambah item</button>
                </div>
                <input type="hidden" name="items_present" value="1">
                @if ($products->count())
                    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-600 mb-2">Tambah dari Bundle</div>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                            <div class="md:col-span-3">
                                <select id="bundle-product"
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
                                <input id="bundle-qty" type="number" value="1" step="0.01" min="0.01"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-right"
                                    placeholder="Qty bundle">
                            </div>
                            <div class="flex items-center">
                                <button type="button" onclick="addBundleItems()"
                                    class="w-full rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Tambah Bundle
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 mb-2">
                    <div class="col-span-4">Item</div>
                    <div class="col-span-2 text-right">Qty</div>
                    <div class="col-span-2">Satuan</div>
                    <div class="col-span-3 text-right">Harga</div>
                    <div class="col-span-1 text-right">Aksi</div>
                </div>
                <div id="item-container">
                    @php
                        $oldJudul = old('item_judul');
                    @endphp
                    @if (is_array($oldJudul))
                        @foreach ($oldJudul as $i => $judul)
                            <div class="grid grid-cols-12 gap-2 mb-2 item-row">
                                <div class="col-span-4">
                                    <input type="text" name="item_judul[]" value="{{ $judul }}"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                        placeholder="Nama item">
                                    <input type="hidden" name="item_tipe[]" value="{{ old('item_tipe.' . $i, 'custom') }}">
                                    <input type="hidden" name="item_product_id[]" value="{{ old('item_product_id.' . $i) }}">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" name="item_qty[]"
                                        value="{{ old('item_qty.' . $i, 1) }}" step="0.01" min="0.01"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                                </div>
                                <div class="col-span-2">
                                    <input type="text" name="item_satuan[]"
                                        value="{{ old('item_satuan.' . $i) }}"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                        placeholder="ls">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="item_harga[]"
                                        value="{{ old('item_harga.' . $i, 0) }}" min="0"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                                </div>
                                <div class="col-span-1 flex items-center justify-end">
                                    <button type="button" onclick="this.closest('.item-row').remove()"
                                        class="text-red-500 text-sm">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    @elseif ($usulan->items->count())
                        @foreach ($usulan->items as $i => $item)
                            <div class="grid grid-cols-12 gap-2 mb-2 item-row">
                                <div class="col-span-4">
                                    <input type="text" name="item_judul[]" value="{{ $item->judul }}"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                        placeholder="Nama item">
                                    <input type="hidden" name="item_tipe[]" value="{{ $item->tipe ?? 'custom' }}">
                                    <input type="hidden" name="item_product_id[]" value="{{ $item->product_id }}">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" name="item_qty[]"
                                        value="{{ $item->qty ?? 1 }}" step="0.01" min="0.01"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                                </div>
                                <div class="col-span-2">
                                    <input type="text" name="item_satuan[]"
                                        value="{{ $item->satuan }}"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                        placeholder="ls">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="item_harga[]"
                                        value="{{ $item->harga ?? 0 }}" min="0"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                                </div>
                                <div class="col-span-1 flex items-center justify-end">
                                    <button type="button" onclick="this.closest('.item-row').remove()"
                                        class="text-red-500 text-sm">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="grid grid-cols-12 gap-2 mb-2 item-row">
                            <div class="col-span-4">
                                <input type="text" name="item_judul[]"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    placeholder="Nama item">
                                <input type="hidden" name="item_tipe[]" value="custom">
                                <input type="hidden" name="item_product_id[]" value="">
                            </div>
                            <div class="col-span-2">
                                <input type="number" name="item_qty[]" value="1" step="0.01" min="0.01"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                            </div>
                            <div class="col-span-2">
                                <input type="text" name="item_satuan[]"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    placeholder="ls">
                            </div>
                            <div class="col-span-3">
                                <input type="number" name="item_harga[]" value="0" min="0"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                            </div>
                            <div class="col-span-1 flex items-center justify-end">
                                <button type="button" onclick="this.closest('.item-row').remove()"
                                    class="text-red-500 text-sm">Hapus</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <label class="block text-xs font-semibold mb-2">Lampiran Dokumen</label>

                {{-- Existing attachments --}}
                @if ($usulan->attachments->count())
                    <div class="mb-4 space-y-2">
                        @foreach ($usulan->attachments as $att)
                            <div class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                                <div>
                                    <span class="text-xs bg-slate-200 px-2 py-0.5 rounded mr-2">{{ $att->tipe }}</span>
                                    <span class="text-sm">{{ $att->nama_file }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ Storage::url($att->path) }}" target="_blank"
                                        class="text-blue-600 text-sm hover:underline">Lihat</a>
                                    <button type="button" onclick="deleteAttachment(this, {{ $att->id }})"
                                        class="text-red-500 text-sm hover:underline">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div id="attachment-container">
                    <div class="flex gap-2 mb-2 attachment-row">
                        <select name="attachment_types[]" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="survei">Survei</option>
                            <option value="dokumen">Dokumen</option>
                            <option value="foto">Foto</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <input type="file" name="attachments[]"
                            class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="button" onclick="addAttachmentRow()" class="mt-2 text-sm text-blue-600 hover:underline">+
                    Tambah lampiran</button>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('usulan.show', $usulan->id) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                <button type="submit" name="status" value="draft"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Simpan
                    Draft</button>
                <button type="submit" name="status" value="menunggu"
                    class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Kirim ke
                    Sales</button>
            </div>
        </form>

        <form id="delete-att-form" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
    </div>

    <script>
        function addAttachmentRow() {
            const container = document.getElementById('attachment-container');
            const row = document.createElement('div');
            row.className = 'flex gap-2 mb-2 attachment-row';
            row.innerHTML = `
                        <select name="attachment_types[]" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="survei">Survei</option>
                            <option value="dokumen">Dokumen</option>
                            <option value="foto">Foto</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <input type="file" name="attachments[]" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-sm">Hapus</button>
                    `;
            container.appendChild(row);
        }

        function addItemRow() {
            const container = document.getElementById('item-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-2 mb-2 item-row';
            row.innerHTML = renderItemRow();
            container.appendChild(row);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderItemRow(data = {}) {
            const judul = escapeHtml(data.judul ?? '');
            const qty = escapeHtml(data.qty ?? 1);
            const satuan = escapeHtml(data.satuan ?? '');
            const harga = escapeHtml(data.harga ?? 0);
            const tipe = escapeHtml(data.tipe ?? 'custom');
            const productId = escapeHtml(data.product_id ?? '');
            return `
                <div class="col-span-4">
                    <input type="text" name="item_judul[]" value="${judul}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nama item">
                    <input type="hidden" name="item_tipe[]" value="${tipe}">
                    <input type="hidden" name="item_product_id[]" value="${productId}">
                </div>
                <div class="col-span-2">
                    <input type="number" name="item_qty[]" value="${qty}" step="0.01" min="0.01" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                </div>
                <div class="col-span-2">
                    <input type="text" name="item_satuan[]" value="${satuan}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="ls">
                </div>
                <div class="col-span-3">
                    <input type="number" name="item_harga[]" value="${harga}" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                </div>
                <div class="col-span-1 flex items-center justify-end">
                    <button type="button" onclick="this.closest('.item-row').remove()" class="text-red-500 text-sm">Hapus</button>
                </div>
            `;
        }

        const bundleProducts = @json($bundleProducts);

        function addBundleItems() {
            const select = document.getElementById('bundle-product');
            const qtyInput = document.getElementById('bundle-qty');
            const productId = parseInt(select.value || '0', 10);
            if (!productId) {
                alert('Pilih product terlebih dahulu.');
                return;
            }
            const product = bundleProducts.find(p => p.id === productId);
            if (!product) {
                alert('Product tidak ditemukan.');
                return;
            }
            let bundleQty = parseFloat(qtyInput.value || '1');
            if (!Number.isFinite(bundleQty) || bundleQty <= 0) bundleQty = 1;

            const container = document.getElementById('item-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-2 mb-2 item-row';
            row.innerHTML = renderItemRow({
                judul: product.nama,
                qty: bundleQty,
                satuan: product.satuan || '',
                harga: product.unit_price || 0,
                tipe: 'bundle',
                product_id: product.id,
            });
            container.appendChild(row);
        }

        function deleteAttachment(btn, id) {
            if (!confirm('Hapus lampiran ini?')) return;
            const form = document.getElementById('delete-att-form');
            form.action = `{{ url('/usulan/attachment') }}/${id}`;
            form.submit();
        }
    </script>
@endsection
