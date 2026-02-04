@extends('layouts.app', ['title' => 'Daftar Invoice'])

@section('content')
    <div class="flex items-center justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Daftar Invoice</h1>
        </div>
        <a href="{{ route('invoices.create') }}"
           class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
           Buat Invoice Baru
        </a>
    </div>

    <form method="GET" class="mb-4">
        <div class="flex gap-2">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor invoice, judul..."
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
            <a href="{{ route('invoices.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No Invoice</th>
                        <th class="px-4 py-3 text-left font-semibold">Judul</th>
                        <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($data as $row)
                        @php
                            $docNo = $row->docNumber?->doc_no ?? 'INV-'.str_pad($row->id, 6, '0', STR_PAD_LEFT);
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap font-semibold">{{ $docNo }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->judul }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $row->user->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->tgl_invoice?->format('d M Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->jatuh_tempo?->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if($row->status === 'draft')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">Draft</span>
                                @elseif($row->status === 'sent')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-600">Sent</span>
                                @elseif($row->status === 'paid')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-600">Paid</span>
                                @elseif($row->status === 'cancelled')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-600">Cancelled</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">{{ $row->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                Rp {{ number_format($row->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('invoices.show', $row->id) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Detail</a>
                                    <a href="{{ route('invoices.edit', $row->id) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Edit</a>
                                    
                                    <form action="{{ route('invoices.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Hapus invoice ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada invoice.</td>
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
