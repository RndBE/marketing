@extends('layouts.app', ['title' => 'Ubah Purchase Order'])

@section('content')
    @php
        $dariUsulan = $po->usulan_id !== null;
        $pelangganLuar = $po->isExternalCustomerOrder();
        // Pemasok dan status hanya diatur sendiri pada PO yang tidak punya lawan
        // transaksi di dalam sistem -- sama seperti aturan di form pembuatan.
        $aturSendiri = ! $dariUsulan && $po->supplier_company_id === null && ! $pelangganLuar;
        $dikirimUlang = $po->status === 'rejected' && $po->supplier_company_id !== null;
        // Mode terbatas: PO sudah berjalan. Field lain tetap ditampilkan supaya isinya
        // terlihat, tapi dimatikan -- hanya keterangan dokumen yang bisa disunting.
        $hanyaKeterangan = $hanyaKeterangan ?? false;
        $kunci = $hanyaKeterangan ? 'disabled' : '';
        $wajib = $hanyaKeterangan ? 'disabled' : 'required';
        $kelasKunci = 'disabled:bg-slate-50 disabled:text-slate-500';
    @endphp
    <div class="max-w-2xl">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">{{ $hanyaKeterangan ? 'Ubah Keterangan Purchase Order' : 'Ubah Purchase Order' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $po->nomor_po ?: 'Purchase Order' }}</p>
        </div>

        @if($hanyaKeterangan)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <div class="text-xs font-semibold text-amber-800">PO sudah berjalan</div>
                <p class="mt-1">Nilai, tanggal, dan dokumen PO tidak dapat diubah lagi karena termin pembayaran sudah dibuat. Yang masih bisa diperbaiki hanya keterangan yang tercetak di PDF.</p>
            </div>
        @endif

        @if($dikirimUlang)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="text-xs font-semibold text-amber-800">PO ini ditolak penjual</div>
                @if($po->verification_notes)
                    <p class="mt-1 text-sm text-amber-900">Alasan: {{ $po->verification_notes }}</p>
                @endif
                <p class="mt-2 text-xs text-amber-800">Setelah disimpan, PO otomatis dikirim ulang ke {{ $po->supplierCompany?->name ?? 'perusahaan penjual' }} untuk diverifikasi.</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="font-semibold">Perubahan belum dapat disimpan:</div>
                <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white px-5 pt-2 pb-4">
            <form method="POST" action="{{ route('purchase-orders.update', $po) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @method('PUT')


                @if($dariUsulan)
                    <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                        <div class="text-xs font-semibold text-violet-700">PO berdasarkan penawaran yang disetujui</div>
                        <div class="mt-1 text-sm font-medium">{{ $po->company?->name }} → {{ $po->supplierCompany?->name ?? $po->supplier_nama }}</div>
                        <p class="mt-2 text-xs text-violet-700">Pemasok dan jenis transaksi mengikuti permintaan harga, jadi tidak dapat diubah di sini.</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold mb-1">Nomor PO</label>
                    <input name="nomor_po" value="{{ old('nomor_po', $po->nomor_po) }}" {{ $kunci }}
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}"
                        placeholder="Contoh: PO-2026-001">
                    <div class="mt-1 text-xs text-slate-500">Dikosongkan berarti nomor dibuatkan ulang oleh sistem.</div>
                    @error('nomor_po') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Judul PO</label>
                    <input name="judul" value="{{ old('judul', $po->judul) }}" {{ $wajib }}
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}">
                    @error('judul') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                @if($pelangganLuar)
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nama Pelanggan</label>
                        <input name="pembeli_nama" value="{{ old('pembeli_nama', $po->pembeli_nama) }}" {{ $wajib }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}">
                        @error('pembeli_nama') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Alamat Pelanggan</label>
                        <textarea name="pembeli_alamat" rows="3" {{ $kunci }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}"
                            placeholder="Alamat pelanggan (opsional)">{{ old('pembeli_alamat', $po->pembeli_alamat) }}</textarea>
                        @error('pembeli_alamat') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nama Supplier</label>
                        <input name="supplier_nama" value="{{ old('supplier_nama', $po->supplierCompany?->name ?? $po->supplier_nama) }}"
                            {{ $aturSendiri && ! $hanyaKeterangan ? 'required' : 'disabled' }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:bg-slate-50 disabled:text-slate-500">
                        @error('supplier_nama') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Alamat Supplier</label>
                        <textarea name="supplier_alamat" rows="3" {{ $aturSendiri && ! $hanyaKeterangan ? '' : 'disabled' }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:bg-slate-50 disabled:text-slate-500"
                            placeholder="Alamat supplier (opsional)">{{ old('supplier_alamat', $po->supplierCompany?->address ?? $po->supplier_alamat) }}</textarea>
                        @error('supplier_alamat') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tanggal PO</label>
                        <input type="date" name="tgl_po" value="{{ old('tgl_po', $po->tgl_po?->format('Y-m-d')) }}" {{ $wajib }}
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}">
                        @error('tgl_po') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    @if($aturSendiri)
                        <div>
                            <label class="block text-sm font-semibold mb-1">Status</label>
                            @php $status = old('status', $po->status); @endphp
                            <select name="status" {{ $wajib }}
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}">
                                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Menunggu verifikasi</option>
                                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                            @error('status') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Jenis Transaksi</label>
                    @php $jenisTransaksi = old('jenis_transaksi', $po->jenis_transaksi); @endphp
                    <select name="jenis_transaksi" {{ $dariUsulan || $hanyaKeterangan ? 'disabled' : 'required' }}
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:bg-slate-50 disabled:text-slate-500">
                        <option value="barang" {{ $jenisTransaksi === 'barang' ? 'selected' : '' }}>Barang</option>
                        <option value="jasa" {{ $jenisTransaksi === 'jasa' ? 'selected' : '' }}>Jasa</option>
                        <option value="campuran" {{ $jenisTransaksi === 'campuran' ? 'selected' : '' }}>Barang + Jasa</option>
                    </select>
                    @error('jenis_transaksi') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Total (Rp)</label>
                    <input type="number" min="1" name="total" value="{{ old('total', (int) round((float) $po->total)) }}" {{ $wajib }}
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 {{ $kelasKunci }}">
                    @error('total') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    @if($quotationTotal !== null)
                        <div class="mt-1 text-xs text-slate-500">Nilai penawaran: Rp {{ number_format($quotationTotal, 0, ',', '.') }}. Jika berbeda, penjual wajib memberikan catatan saat verifikasi.</div>
                    @endif
                    @if($totalTerjadwal > 0)
                        <div class="mt-1 text-xs text-amber-700">Sudah terjadwal dalam termin: Rp {{ number_format($totalTerjadwal, 0, ',', '.') }}. Total PO tidak boleh lebih kecil dari nilai ini.</div>
                    @endif
                </div>


                <div>
                    @php
                        $keteranganBawaan = $po->catatan ?: implode("\n", $po->penawaran?->syaratDokumen() ?? []);
                    @endphp
                    <label class="block text-sm font-semibold mb-1">Catatan / Keterangan</label>
                    <textarea name="catatan" rows="6"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('catatan', $keteranganBawaan) }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">Satu baris = satu poin pada bagian <strong>Keterangan</strong> di PDF PO. Boleh diubah, ditambah, atau dihapus.</div>
                    @error('catatan') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>


                <div>
                    <label class="block text-sm font-semibold mb-1">Dokumen PO</label>
                    @if($po->po_file_path)
                        <div class="mb-1 text-xs text-slate-500">
                            Dokumen saat ini: <a href="{{ route('purchase-orders.document.download', $po) }}" class="font-semibold text-blue-600">Unduh dokumen</a>
                        </div>
                    @endif
                    <input type="file" name="po_file" accept=".pdf,.jpg,.jpeg,.png" {{ $kunci }}
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold {{ $kelasKunci }}">
                    <div class="mt-1 text-xs text-slate-500">PDF/JPG/PNG, maksimal 10 MB. Kosongkan untuk tetap memakai dokumen lama.</div>
                    @error('po_file') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>


                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    <a href="{{ route('purchase-orders.show', $po) }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">Batal</a>
                    @if($hanyaKeterangan)
                        <a href="{{ route('purchase-orders.pdf', $po) }}" target="_blank"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">Pratinjau PDF</a>
                    @endif
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        {{ $hanyaKeterangan ? 'Simpan Keterangan' : ($dikirimUlang ? 'Simpan & Kirim Ulang' : 'Simpan Perubahan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
