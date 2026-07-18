@extends('layouts.app', ['title' => 'Daftar Invoice'])

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Daftar Invoice</h1>
        </div>
        <a href="{{ route('invoices.create') }}"
           class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 sm:w-auto">
           Buat Invoice Baru
        </a>
    </div>

    <form method="GET" class="mb-4">
        <div class="grid grid-cols-2 gap-2 sm:flex">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor invoice, judul..."
                class="col-span-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 sm:col-span-1">
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Cari</button>
            <a href="{{ route('invoices.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div data-invoice-desktop-table class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white md:block">
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
                                @include('invoices.partials.status-badge', ['status' => $row->status])
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
                                    
                                    <form action="{{ route('invoices.destroy', $row->id) }}" method="POST"
                                        data-confirm-title="Hapus Invoice?"
                                        data-confirm-delete="Invoice {{ $row->docNumber?->doc_no ?? $row->judul }} akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.">
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
    </div>

    <div data-invoice-mobile-list class="space-y-3 md:hidden">
        @forelse($data as $row)
            @php
                $docNo = $row->docNumber?->doc_no ?? 'INV-'.str_pad($row->id, 6, '0', STR_PAD_LEFT);
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('invoices.show', $row->id) }}"
                            class="break-words text-xs font-semibold text-slate-500 underline-offset-4 hover:text-blue-700 hover:underline">
                            {{ $docNo }}
                        </a>
                        <h2 class="mt-1 break-words font-semibold text-slate-900">{{ $row->judul }}</h2>
                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $row->user->name ?? '-' }}</p>
                    </div>
                    @include('invoices.partials.status-badge', ['status' => $row->status])
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 border-y border-slate-100 py-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Tanggal</dt>
                        <dd class="mt-0.5 font-medium text-slate-700">{{ $row->tgl_invoice?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Jatuh tempo</dt>
                        <dd class="mt-0.5 font-medium text-slate-700">{{ $row->jatuh_tempo?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs text-slate-500">Total</dt>
                        <dd class="mt-0.5 text-base font-semibold text-slate-900">
                            Rp {{ number_format($row->grand_total, 0, ',', '.') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="{{ route('invoices.show', $row->id) }}"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white hover:bg-slate-800">
                        Detail
                    </a>
                    <a href="{{ route('invoices.edit', $row->id) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Edit
                    </a>
                    <form class="col-span-2" action="{{ route('invoices.destroy', $row->id) }}" method="POST"
                        data-confirm-title="Hapus Invoice?"
                        data-confirm-delete="Invoice {{ $row->docNumber?->doc_no ?? $row->judul }} akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-100">
                            Hapus
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div data-invoice-mobile-empty class="rounded-2xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
                Belum ada invoice.
            </div>
        @endforelse
    </div>

    @if($data->hasPages())
        <div class="mt-4">
            {{ $data->links() }}
        </div>
    @endif
@endsection
