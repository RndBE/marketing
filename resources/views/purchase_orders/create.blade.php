@extends('layouts.app', ['title' => 'Buat Purchase Order'])

@section('content')
    @php
        $linkedTotal = $usulan?->penawaran?->calcGrandTotal() ?? 0;
    @endphp
    <div class="max-w-2xl">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">Buat Purchase Order</h1>
            <p class="mt-1 text-sm text-slate-500">PO akan dikirim oleh perusahaan pembeli kepada perusahaan penjual.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 pt-2 pb-4">
            <form method="POST" action="{{ route('purchase-orders.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @if($usulan)
                    <input type="hidden" name="usulan_id" value="{{ $usulan->id }}">
                    <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                        <div class="text-xs font-semibold text-violet-700">PO berdasarkan penawaran yang disetujui</div>
                        <div class="mt-1 text-sm font-medium">{{ $usulan->company?->name }} → {{ $usulan->targetCompany?->name }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $usulan->penawaran?->docNumber?->doc_no ?? 'Penawaran terhubung' }}</div>
                        <p class="mt-2 text-xs text-violet-700">Setelah dikirim, perusahaan penjual akan memverifikasi PO dan membuat jadwal termin.</p>
                    </div>
                @endif

                @unless($usulan)
                    @php $sumber = old('sumber', 'internal'); @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-semibold">Jenis Purchase Order</div>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label class="flex cursor-pointer gap-3 rounded-xl border bg-white p-3 {{ $sumber === 'internal' ? 'border-violet-300 ring-2 ring-violet-100' : 'border-slate-200' }}">
                                <input type="radio" name="sumber" value="internal" data-po-sumber @checked($sumber === 'internal') class="mt-1">
                                <span>
                                    <span class="block text-sm font-semibold">Pembelian ke supplier</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">Perusahaan Anda membeli. Anda mengisi data supplier.</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer gap-3 rounded-xl border bg-white p-3 {{ $sumber === 'pelanggan_luar' ? 'border-blue-300 ring-2 ring-blue-100' : 'border-slate-200' }}">
                                <input type="radio" name="sumber" value="pelanggan_luar" data-po-sumber @checked($sumber === 'pelanggan_luar') class="mt-1">
                                <span>
                                    <span class="block text-sm font-semibold">PO dari pelanggan</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">Perusahaan Anda menjual. Unggah PO yang diterima dari pelanggan luar.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                @endunless

                <div>
                    <label class="block text-sm font-semibold mb-1">Nomor PO (opsional)</label>
                    <input name="nomor_po" value="{{ old('nomor_po') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: PO-2026-001">
                    @error('nomor_po') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Judul PO</label>
                    <input name="judul" value="{{ old('judul', $usulan?->judul) }}" data-po-required="internal" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: PO Pengadaan Perangkat">
                    @error('judul') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                @unless($usulan)
                    <div data-po-fields="pelanggan_luar" class="{{ $sumber === 'pelanggan_luar' ? '' : 'hidden' }} space-y-3">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Nama Pelanggan</label>
                            <input name="pembeli_nama" value="{{ old('pembeli_nama') }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                                placeholder="Contoh: Dinas PUPR Kabupaten Sleman">
                            @error('pembeli_nama') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Alamat Pelanggan</label>
                            <textarea name="pembeli_alamat" rows="3"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                                placeholder="Alamat pelanggan (opsional)">{{ old('pembeli_alamat') }}</textarea>
                            @error('pembeli_alamat') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                @endunless

                <div data-po-fields="internal" class="{{ !$usulan && ($sumber ?? 'internal') === 'pelanggan_luar' ? 'hidden' : '' }} space-y-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nama Supplier</label>
                        <input name="supplier_nama" value="{{ old('supplier_nama', $usulan?->targetCompany?->name) }}" {{ $usulan ? 'readonly' : '' }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                            placeholder="Contoh: PT Sumber Teknologi">
                        @error('supplier_nama') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Alamat Supplier</label>
                        <textarea name="supplier_alamat" rows="3" {{ $usulan ? 'readonly' : '' }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                            placeholder="Alamat supplier (opsional)">{{ old('supplier_alamat', $usulan?->targetCompany?->address) }}</textarea>
                        @error('supplier_alamat') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tanggal PO</label>
                        <input type="date" name="tgl_po" value="{{ old('tgl_po', date('Y-m-d')) }}" data-po-required="internal" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @error('tgl_po') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div data-po-hide="pelanggan_luar">
                        <label class="block text-sm font-semibold mb-1">Status setelah disimpan</label>
                        <select name="status" {{ $usulan ? 'disabled' : '' }}
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            @php
                                $status = old('status', $usulan ? 'submitted' : 'draft');
                            @endphp
                            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Menunggu verifikasi</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                        @if($usulan)<input type="hidden" name="status" value="submitted">@endif
                        @error('status') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Jenis Transaksi</label>
                    <select name="jenis_transaksi" data-po-required="internal" required {{ $usulan ? 'disabled' : '' }}
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @php
                            $jenisTransaksi = old('jenis_transaksi', $usulan?->jenis_transaksi ?? 'barang');
                        @endphp
                        <option value="barang" {{ $jenisTransaksi === 'barang' ? 'selected' : '' }}>Barang</option>
                        <option value="jasa" {{ $jenisTransaksi === 'jasa' ? 'selected' : '' }}>Jasa</option>
                        <option value="campuran" {{ $jenisTransaksi === 'campuran' ? 'selected' : '' }}>Barang + Jasa</option>
                    </select>
                    @if($usulan)<input type="hidden" name="jenis_transaksi" value="{{ $usulan->jenis_transaksi }}">@endif
                    @error('jenis_transaksi') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Total (Rp)</label>
                    <input type="number" min="1" name="total" value="{{ old('total', $linkedTotal) }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: 25000000">
                    @error('total') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    @if($usulan)
                        <div class="mt-1 text-xs text-slate-500">Nilai penawaran: Rp {{ number_format($linkedTotal, 0, ',', '.') }}. Jika berbeda, penjual wajib memberikan catatan saat verifikasi.</div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Catatan</label>
                    <textarea name="catatan" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Catatan tambahan (opsional)">{{ old('catatan') }}</textarea>
                    @error('catatan') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Dokumen PO {{ $usulan ? '*' : '' }}</label>
                    <input type="file" name="po_file" accept=".pdf,.jpg,.jpeg,.png" data-po-required="pelanggan_luar" {{ $usulan ? 'required' : '' }}
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold">
                    <div class="mt-1 text-xs text-slate-500">PDF/JPG/PNG, maksimal 10 MB.</div>
                    @error('po_file') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    <a href="{{ route('purchase-orders.index') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        {{ $usulan ? 'Kirim Purchase Order' : 'Simpan Purchase Order' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @unless($usulan)
        @push('scripts')
            <script>
                (() => {
                    const pilihan = document.querySelectorAll('[data-po-sumber]');
                    const kelompok = document.querySelectorAll('[data-po-fields]');
                    if (!pilihan.length) return;

                    const opsional = document.querySelectorAll('[data-po-required]');
                    const disembunyikan = document.querySelectorAll('[data-po-hide]');

                    const terapkan = () => {
                        const aktif = document.querySelector('[data-po-sumber]:checked')?.value || 'internal';

                        kelompok.forEach((el) => el.classList.toggle('hidden', el.dataset.poFields !== aktif));
                        // Kolom tersembunyi dilepas dari validasi browser supaya tidak
                        // memblokir submit dengan pesan pada kolom yang tak terlihat.
                        kelompok.forEach((el) => {
                            const tersembunyi = el.classList.contains('hidden');
                            el.querySelectorAll('input, textarea').forEach((field) => {
                                field.disabled = tersembunyi;
                            });
                        });

                        // Wajib atau tidaknya sebuah kolom bergantung jenis PO yang dipilih.
                        opsional.forEach((field) => {
                            field.required = field.dataset.poRequired === aktif;
                        });

                        disembunyikan.forEach((el) => el.classList.toggle('hidden', el.dataset.poHide === aktif));
                    };

                    pilihan.forEach((el) => el.addEventListener('change', terapkan));
                    terapkan();
                })();
            </script>
        @endpush
    @endunless
@endsection
