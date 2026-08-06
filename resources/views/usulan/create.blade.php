@extends('layouts.app')

@section('content')
    <div class="w-full max-w-4xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Buat Permintaan Harga</h1>
            <p class="mt-1 text-sm text-slate-500">Permintaan dapat dikirim dari perusahaan mana pun ke perusahaan tujuan.</p>
        </div>

        <form method="POST" action="{{ route('usulan.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Judul Permintaan *</label>
                    <input type="text" name="judul" value="{{ old('judul') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Kirim ke Perusahaan *</label>
                        <select name="target_company_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                            <option value="">Pilih perusahaan tujuan</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (int) old('target_company_id') === (int) $company->id ? 'selected' : '' }}>
                                    {{ $company->code }} - {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('target_company_id') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Jenis Transaksi *</label>
                        <select name="jenis_transaksi" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                            @php
                                $jenis = old('jenis_transaksi', 'barang');
                            @endphp
                            <option value="barang" {{ $jenis === 'barang' ? 'selected' : '' }}>Barang</option>
                            <option value="jasa" {{ $jenis === 'jasa' ? 'selected' : '' }}>Jasa</option>
                            <option value="campuran" {{ $jenis === 'campuran' ? 'selected' : '' }}>Barang + Jasa</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">PIC/Klien</label>
                        <select name="pic_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Pilih PIC</option>
                            @foreach ($pics as $pic)
                                <option value="{{ $pic->id }}">{{ $pic->instansi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Nilai Estimasi</label>
                        <input type="number" name="nilai_estimasi" value="{{ old('nilai_estimasi', 0) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" min="0">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Tanggal Dibutuhkan</label>
                    <input type="date" name="tanggal_dibutuhkan" value="{{ old('tanggal_dibutuhkan') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Jelaskan detail prospek, kebutuhan klien, dll...">{{ old('deskripsi') }}</textarea>
                </div>
            </div>

            @include('usulan.partials.signature-fields')

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-semibold">Item yang Diminta (Opsional)</label>
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
                <div class="mb-2 hidden grid-cols-12 gap-2 text-xs font-semibold text-slate-500 sm:grid">
                    <div class="sm:col-span-4">Item</div>
                    <div class="text-right sm:col-span-2">Qty</div>
                    <div class="sm:col-span-2">Satuan</div>
                    <div class="text-right sm:col-span-3">Harga</div>
                    <div class="text-right sm:col-span-1">Aksi</div>
                </div>
                <div id="item-container">
                    @php
                        $oldJudul = old('item_judul') ?? [];
                    @endphp
                    @if (! empty($oldJudul))
                        @foreach ($oldJudul as $i => $judul)
                            @include('usulan.partials.item-row', [
                                'index' => $i,
                                'judul' => $judul,
                                'qty' => old('item_qty.'.$i, 1),
                                'satuan' => old('item_satuan.'.$i),
                                'harga' => old('item_harga.'.$i, 0),
                                'tipe' => old('item_tipe.'.$i, 'custom'),
                                'productId' => old('item_product_id.'.$i),
                                'points' => old('item_poin.'.$i, []),
                            ])
                        @endforeach
                    @else
                        @include('usulan.partials.item-row', [
                            'index' => 0,
                            'judul' => '',
                            'qty' => 1,
                            'satuan' => '',
                            'harga' => 0,
                            'tipe' => 'custom',
                            'productId' => '',
                            'points' => [],
                        ])
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <label class="block text-xs font-semibold mb-2">Lampiran Dokumen</label>
                <div id="attachment-container">
                    <div class="mb-2 flex flex-col gap-2 attachment-row sm:flex-row">
                        <select name="attachment_types[]" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm sm:w-auto">
                            <option value="survei">Survei</option>
                            <option value="dokumen">Dokumen</option>
                            <option value="foto">Foto</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <input type="file" name="attachments[]"
                            class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="button" onclick="addAttachmentRow()" class="mt-2 text-sm text-blue-600 hover:underline">+
                    Tambah lampiran</button>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('usulan.index') }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">Batal</a>
                <button type="submit" name="status" value="draft"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Simpan
                    Draft</button>
                <button type="submit" name="status" value="menunggu"
                    class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Kirim
                    ke
                    Perusahaan Tujuan</button>
            </div>
        </form>
    </div>

    <script>
        function addAttachmentRow() {
            const container = document.getElementById('attachment-container');
            const row = document.createElement('div');
            row.className = 'mb-2 flex flex-col gap-2 attachment-row sm:flex-row';
            row.innerHTML = `
                                <select name="attachment_types[]" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm sm:w-auto">
                                    <option value="survei">Survei</option>
                                    <option value="dokumen">Dokumen</option>
                                    <option value="foto">Foto</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                                <input type="file" name="attachments[]" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-sm">Hapus</button>
                            `;
            container.appendChild(row);
        }

        let nextItemIndex = Array.from(document.querySelectorAll('.item-row'))
            .reduce((max, row) => Math.max(max, Number(row.dataset.itemIndex) || 0), -1) + 1;

        function addItemRow() {
            const container = document.getElementById('item-container');
            const row = document.createElement('div');
            const itemIndex = nextItemIndex++;
            row.className = 'mb-3 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-3 item-row sm:mb-3 sm:grid-cols-12';
            row.dataset.itemIndex = itemIndex;
            row.innerHTML = renderItemRow({}, itemIndex);
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

        function renderPointInput(itemIndex, value = '') {
            return `
                <div class="item-point-row flex items-center gap-2">
                    <span class="item-point-label w-12 shrink-0 text-xs font-semibold text-slate-500"></span>
                    <input type="text" name="item_poin[${itemIndex}][]" value="${escapeHtml(value)}" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Contoh: Material stainless steel 304">
                    <button type="button" onclick="removeItemPoint(this)" class="text-xs font-semibold text-red-500">Hapus</button>
                </div>
            `;
        }

        function renumberItemPoints(container) {
            container.querySelectorAll('.item-point-label').forEach((label, index) => {
                label.textContent = `Poin ${index + 1}`;
            });
        }

        function addItemPoint(button) {
            const itemRow = button.closest('.item-row');
            const container = itemRow.querySelector('.item-point-container');
            container.insertAdjacentHTML('beforeend', renderPointInput(itemRow.dataset.itemIndex));
            renumberItemPoints(container);
            container.lastElementChild.querySelector('input').focus();
        }

        function removeItemPoint(button) {
            const container = button.closest('.item-point-container');
            button.closest('.item-point-row').remove();
            renumberItemPoints(container);
        }

        function renderItemRow(data = {}, itemIndex) {
            const judul = escapeHtml(data.judul ?? '');
            const qty = escapeHtml(data.qty ?? 1);
            const satuan = escapeHtml(data.satuan ?? '');
            const harga = escapeHtml(data.harga ?? 0);
            const tipe = escapeHtml(data.tipe ?? 'custom');
            const productId = escapeHtml(data.product_id ?? '');
            const points = Array.isArray(data.points) ? data.points : [];
            return `
                <div class="sm:col-span-4">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Item</span>
                    <input type="text" name="item_judul[${itemIndex}]" value="${judul}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nama item">
                    <input type="hidden" name="item_tipe[${itemIndex}]" value="${tipe}">
                    <input type="hidden" name="item_product_id[${itemIndex}]" value="${productId}">
                </div>
                <div class="sm:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Qty</span>
                    <input type="number" name="item_qty[${itemIndex}]" value="${qty}" step="0.01" min="0.01" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                </div>
                <div class="sm:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Satuan</span>
                    <input type="text" name="item_satuan[${itemIndex}]" value="${satuan}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Paket/Unit">
                </div>
                <div class="sm:col-span-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 sm:hidden">Estimasi harga</span>
                    <input type="number" name="item_harga[${itemIndex}]" value="${harga}" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-right">
                </div>
                <div class="flex items-center justify-end sm:col-span-1">
                    <button type="button" onclick="this.closest('.item-row').remove()" class="text-red-500 text-sm">Hapus</button>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 sm:col-span-12">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold text-slate-700">Detail / Spesifikasi per Poin</div>
                            <div class="text-[11px] text-slate-500">Tambahkan poin kebutuhan khusus untuk item ini.</div>
                        </div>
                        <button type="button" onclick="addItemPoint(this)" class="shrink-0 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">+ Tambah Poin</button>
                    </div>
                    <div class="item-point-container space-y-2">
                        ${points.map(point => renderPointInput(itemIndex, point)).join('')}
                    </div>
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
            const itemIndex = nextItemIndex++;
            row.className = 'mb-3 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-3 item-row sm:mb-3 sm:grid-cols-12';
            row.dataset.itemIndex = itemIndex;
            row.innerHTML = renderItemRow({
                judul: product.nama,
                qty: bundleQty,
                satuan: product.satuan || '',
                harga: product.unit_price || 0,
                tipe: 'bundle',
                product_id: product.id,
            }, itemIndex);
            container.appendChild(row);
        }
    </script>
@endsection
