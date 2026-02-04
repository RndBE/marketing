@extends('layouts.app', ['title' => 'Buat Invoice dari Penawaran'])

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Buat Invoice dari Penawaran</h1>
            <div class="text-sm text-slate-500 mt-1">{{ $penawaran->docNumber?->doc_no }} - {{ $penawaran->judul }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <form method="POST" action="{{ route('invoices.store_from_penawaran', $penawaran->id) }}" class="space-y-4"
                x-data="{ type: 'full' }">
                @csrf

                <div class="p-4 bg-slate-50 rounded-xl mb-4">
                    <div class="text-sm font-semibold text-slate-700">Total Penawaran</div>
                    <div class="text-2xl font-bold mt-1">Rp
                        {{ number_format($penawaran->grand_total_calculated, 0, ',', '.') }}</div>
                    <input type="hidden" name="grand_total" value="{{ $penawaran->grand_total_calculated }}">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Jenis Invoice</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="full" class="peer hidden" x-model="type">
                            <div
                                class="rounded-xl border border-slate-200 p-3 text-center hover:bg-slate-50 peer-checked:bg-slate-900 peer-checked:text-white peer-checked:border-slate-900">
                                <span class="font-semibold text-sm">Full Payment (100%)</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="termin" class="peer hidden" x-model="type">
                            <div
                                class="rounded-xl border border-slate-200 p-3 text-center hover:bg-slate-50 peer-checked:bg-slate-900 peer-checked:text-white peer-checked:border-slate-900">
                                <span class="font-semibold text-sm">DP / Termin / Pelunasan</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="type === 'termin'" style="display: none;">
                    <label class="block text-sm font-semibold mb-1">Persentase Pembayaran (%)</label>
                    <input type="number" name="percentage" min="1" max="100" value="50"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: 30">
                    <div class="text-xs text-slate-500 mt-1">Masukkan persentase pembayaran untuk invoice ini.</div>
                </div>

                <div x-show="type === 'termin'" style="display: none;">
                    <label class="block text-sm font-semibold mb-1">Keterangan Item</label>
                    <input type="text" name="termin_name"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Contoh: Pembayaran DP 30%">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Judul Invoice</label>
                    <input name="judul" value="{{ old('judul', 'Invoice ' . $penawaran->judul) }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tanggal Invoice</label>
                        <input type="date" name="tgl_invoice" value="{{ old('tgl_invoice', date('Y-m-d')) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Jatuh Tempo</label>
                        <input type="date" name="jatuh_tempo"
                            value="{{ old('jatuh_tempo', date('Y-m-d', strtotime('+30 days'))) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Catatan</label>
                    <textarea name="catatan" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Catatan tambahan (opsional)">{{ old('catatan') }}</textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('penawaran.show', $penawaran->id) }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Buat Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection