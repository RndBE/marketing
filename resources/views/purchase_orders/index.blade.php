@extends('layouts.app', ['title' => 'Daftar Purchase Order'])

@section('content')
    <div class="flex items-center justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Daftar Purchase Order</h1>
        </div>
        @if(auth()->user()->hasPermission('create-purchase-order'))
            <a href="{{ route('purchase-orders.create') }}"
               class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
               Buat PO Baru
            </a>
        @endif
    </div>

    <form method="GET" class="mb-4">
        <div class="flex gap-2">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor PO, judul, supplier..."
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
            <a href="{{ route('purchase-orders.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No PO</th>
                        <th class="px-4 py-3 text-left font-semibold">Judul</th>
                        <th class="px-4 py-3 text-left font-semibold">Supplier</th>
                        <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($data as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap font-semibold">{{ $row->nomor_po ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->judul }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $row->user->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->supplier_nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->tgl_po?->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if($row->status === 'draft')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">Draft</span>
                                @elseif($row->status === 'submitted')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-600">Submitted</span>
                                @elseif($row->status === 'approved')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-600">Approved</span>
                                @elseif($row->status === 'cancelled')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-600">Cancelled</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">{{ $row->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                Rp {{ number_format($row->total, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('purchase-orders.show', $row->id) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada purchase order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($data->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $data->links() }}
            </div>
        @endif
    </div>
@endsection
