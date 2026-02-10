@extends('layouts.app', ['title' => 'Detail Purchase Order'])

@section('content')
    <div class="flex items-center justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Detail Purchase Order</h1>
            <div class="text-sm text-slate-500 mt-0.5">{{ $po->nomor_po ?? '-' }}</div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchase-orders.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Kembali</a>
            @if(auth()->user()->hasPermission('create-purchase-order'))
                <a href="{{ route('purchase-orders.create') }}"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Buat PO Baru</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-slate-500">Judul</div>
                        <div class="font-semibold">{{ $po->judul }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Tanggal PO</div>
                        <div class="font-semibold">{{ $po->tgl_po?->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Supplier</div>
                        <div class="font-semibold">{{ $po->supplier_nama }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Status</div>
                        <div class="font-semibold">{{ ucfirst($po->status) }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Alamat Supplier</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $po->supplier_alamat ?: '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Catatan</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $po->catatan ?: '-' }}</div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-xs text-slate-500">Total</div>
                <div class="text-2xl font-semibold">Rp {{ number_format($po->total, 0, ',', '.') }}</div>
                <div class="text-xs text-slate-500 mt-2">Dibuat oleh</div>
                <div class="text-sm font-medium">{{ $po->user->name ?? '-' }}</div>
            </div>
        </div>
    </div>
@endsection
