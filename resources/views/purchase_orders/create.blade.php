@extends('layouts.app', ['title' => 'Buat Purchase Order'])

@section('content')
    <div class="max-w-2xl">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">Buat Purchase Order</h1>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 pt-2 pb-4">
            <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-1">Nomor PO (opsional)</label>
                    <input name="nomor_po" value="{{ old('nomor_po') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: PO-2026-001">
                    @error('nomor_po') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Judul PO</label>
                    <input name="judul" value="{{ old('judul') }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: PO Pengadaan Perangkat">
                    @error('judul') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Nama Supplier</label>
                    <input name="supplier_nama" value="{{ old('supplier_nama') }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: PT Sumber Teknologi">
                    @error('supplier_nama') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Alamat Supplier</label>
                    <textarea name="supplier_alamat" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Alamat supplier (opsional)">{{ old('supplier_alamat') }}</textarea>
                    @error('supplier_alamat') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tanggal PO</label>
                        <input type="date" name="tgl_po" value="{{ old('tgl_po', date('Y-m-d')) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @error('tgl_po') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Status</label>
                        <select name="status"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            @php
                                $status = old('status', 'draft');
                            @endphp
                            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Total (Rp)</label>
                    <input type="number" min="0" name="total" value="{{ old('total', 0) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: 25000000">
                    @error('total') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Catatan</label>
                    <textarea name="catatan" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Catatan tambahan (opsional)">{{ old('catatan') }}</textarea>
                    @error('catatan') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('purchase-orders.index') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan Purchase Order
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
