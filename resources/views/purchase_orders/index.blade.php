@extends('layouts.app', ['title' => 'Daftar Purchase Order'])

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Daftar Purchase Order</h1>
            <p class="mt-1 text-sm text-slate-500">PO Keluar dibuat perusahaan Anda; PO Masuk perlu diproses sebagai penjual.</p>
        </div>
        @if(auth()->user()->hasPermission('create-purchase-order'))
            <a href="{{ route('purchase-orders.create') }}"
               class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
               Buat PO Baru
            </a>
        @endif
    </div>

    <div class="mb-3 flex flex-wrap gap-2" aria-label="Filter arah PO">
        <a href="{{ route('purchase-orders.index', array_filter(['q' => $q])) }}"
            class="rounded-lg px-3 py-2 text-sm font-semibold {{ !$direction ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Semua PO</a>
        <a href="{{ route('purchase-orders.index', array_filter(['direction' => 'outgoing', 'q' => $q])) }}"
            class="rounded-lg px-3 py-2 text-sm font-semibold {{ $direction === 'outgoing' ? 'bg-violet-600 text-white' : 'bg-violet-50 text-violet-700' }}">PO Keluar · Anda pembeli</a>
        <a href="{{ route('purchase-orders.index', array_filter(['direction' => 'incoming', 'q' => $q])) }}"
            class="rounded-lg px-3 py-2 text-sm font-semibold {{ $direction === 'incoming' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700' }}">PO Masuk · Anda penjual</a>
    </div>

    <form method="GET" class="mb-4">
        @if($direction)<input type="hidden" name="direction" value="{{ $direction }}">@endif
        <div class="flex flex-col gap-2 sm:flex-row">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor PO, judul, supplier..."
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
            <a href="{{ route('purchase-orders.index', array_filter(['direction' => $direction])) }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No PO</th>
                        <th class="px-4 py-3 text-left font-semibold">Judul</th>
                        <th class="px-4 py-3 text-left font-semibold">Arah Transaksi</th>
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
                            <td class="px-4 py-3">
                                @if($row->isExternalCustomerOrder())
                                    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">PO Masuk</span>
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Pelanggan luar</span>
                                @elseif((int) $row->company_id === (int) $companyId)
                                    <span class="rounded bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-700">PO Keluar</span>
                                    <div class="mt-1 text-xs text-slate-500">Ke {{ $row->supplierCompany?->name ?? $row->supplier_nama }}</div>
                                @else
                                    <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">PO Masuk</span>
                                    <div class="mt-1 text-xs text-slate-500">Dari {{ $row->company?->name ?? '-' }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->tgl_po?->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if($row->status === 'draft')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">Draft</span>
                                @elseif($row->status === 'submitted')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-600">Menunggu Verifikasi</span>
                                @elseif($row->status === 'approved')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-600">Disetujui</span>
                                @elseif(in_array($row->status, ['cancelled', 'rejected'], true))
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-600">{{ $row->status === 'rejected' ? 'Ditolak' : 'Dibatalkan' }}</span>
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
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                <div class="font-semibold text-slate-700">Tidak ada PO pada filter ini</div>
                                <div class="mt-1 text-xs">Ubah filter arah atau kata pencarian.</div>
                            </td>
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
