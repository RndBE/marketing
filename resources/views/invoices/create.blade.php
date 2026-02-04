@extends('layouts.app', ['title' => 'Buat Invoice'])

@section('content')
    <div class="max-w-2xl">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">Buat Invoice Baru</h1>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 pt-2 pb-4 ">
            <form method="POST" action="{{ route('invoices.store') }}" class="space-y-2">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-1">Judul Invoice</label>
                    <input name="judul" value="{{ old('judul') }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: Invoice Proyek Website">
                    @error('judul') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tanggal Invoice</label>
                        <input type="date" name="tgl_invoice" value="{{ old('tgl_invoice', date('Y-m-d')) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @error('tgl_invoice') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Jatuh Tempo</label>
                        <input type="date" name="jatuh_tempo"
                            value="{{ old('jatuh_tempo', date('Y-m-d', strtotime('+30 days'))) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @error('jatuh_tempo') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Catatan</label>
                    <textarea name="catatan" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Catatan tambahan (opsional)">{{ old('catatan') }}</textarea>
                </div>

                <div x-data="{ mode: 'empty' }" class="pt-4 border-t border-slate-100">
                    <label class="block text-sm font-semibold mb-3">Opsi Isi Item</label>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="empty" class="peer hidden" x-model="mode">
                            <div
                                class="rounded-xl border border-slate-200 p-3 text-center hover:bg-slate-50 peer-checked:bg-slate-900 peer-checked:text-white peer-checked:border-slate-900">
                                <span class="font-semibold text-sm">Invoice Kosong</span>
                                <div class="text-[11px] mt-1 opacity-80">Isi item nanti di halaman detail</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="manual" class="peer hidden" x-model="mode">
                            <div
                                class="rounded-xl border border-slate-200 p-3 text-center hover:bg-slate-50 peer-checked:bg-slate-900 peer-checked:text-white peer-checked:border-slate-900">
                                <span class="font-semibold text-sm">Input Item Langsung</span>
                                <div class="text-[11px] mt-1 opacity-80">Untuk DP / Termin / Manual</div>
                            </div>
                        </label>
                    </div>

                    <div x-show="mode === 'manual'" style="display: none;"
                        class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Nama Item / Keterangan Pembayaran</label>
                            <input name="manual_item_name" value="{{ old('manual_item_name') }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                                placeholder="Contoh: DP 30% Proyek Website">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Nominal (Rp)</label>
                            <input name="manual_item_price" value="{{ old('manual_item_price') }}" type="number" min="0"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                                placeholder="Contoh: 5000000">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('invoices.index') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan & Lanjutkan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection