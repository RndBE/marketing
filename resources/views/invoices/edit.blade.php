@extends('layouts.app', ['title' => 'Edit Invoice'])

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Edit Invoice</h1>
            <div class="text-sm text-slate-500 mt-1">{{ $invoice->docNumber?->doc_no }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <form method="POST" action="{{ route('invoices.update', $invoice->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold mb-1">Judul Invoice</label>
                    <input name="judul" value="{{ old('judul', $invoice->judul) }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tanggal Invoice</label>
                        <input type="date" name="tgl_invoice"
                            value="{{ old('tgl_invoice', $invoice->tgl_invoice?->format('Y-m-d')) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Jatuh Tempo</label>
                        <input type="date" name="jatuh_tempo"
                            value="{{ old('jatuh_tempo', $invoice->jatuh_tempo?->format('Y-m-d')) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                        <option value="draft" {{ $invoice->status == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ $invoice->status == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="cancelled" {{ $invoice->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Pajak (%)</label>
                        <input name="tax_percent" type="number" step="0.01"
                            value="{{ old('tax_percent', $invoice->tax_percent) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Diskon (Rp)</label>
                        <input name="discount_amount" type="number"
                            value="{{ old('discount_amount', $invoice->discount_amount) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Catatan</label>
                    <textarea name="catatan" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('catatan', $invoice->catatan) }}</textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('invoices.show', $invoice->id) }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection