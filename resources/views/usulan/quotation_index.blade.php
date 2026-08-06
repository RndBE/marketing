@extends('layouts.app', ['title' => 'Daftar Penawaran Harga'])

@section('content')
    @php
        $statusLabels = [
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'accepted' => 'Disetujui',
            'revision_requested' => 'Revisi Diminta',
            'rejected' => 'Ditolak',
        ];
        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'sent' => 'bg-blue-100 text-blue-700',
            'accepted' => 'bg-emerald-100 text-emerald-700',
            'revision_requested' => 'bg-amber-100 text-amber-800',
            'rejected' => 'bg-red-100 text-red-700',
        ];
    @endphp

    <div class="mb-5">
        <h1 class="text-xl font-semibold">Daftar Penawaran Harga</h1>
        <p class="mt-1 text-sm text-slate-500">
            Penawaran harga yang perusahaan Anda terbitkan atas permintaan yang masuk.
        </p>
    </div>

    <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('usulan.quotation.index', array_filter(['q' => $q])) }}"
                class="rounded-lg px-3 py-2 text-sm font-semibold {{ !$status ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Semua</a>
            @foreach($statusLabels as $value => $label)
                <a href="{{ route('usulan.quotation.index', array_filter(['status' => $value, 'q' => $q])) }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold {{ $status === $value ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex gap-2">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="q" value="{{ $q }}" placeholder="Cari nomor, pekerjaan, atau pembeli"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm sm:w-72">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Cari</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-[860px] w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">No. Penawaran</th>
                        <th class="px-4 py-3 text-left">Pekerjaan</th>
                        <th class="px-4 py-3 text-left">Pembeli</th>
                        <th class="px-4 py-3 text-right">Nilai</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($quotations as $item)
                        @php
                            $penawaran = $item->penawaran;
                            $docNo = $penawaran?->docNumber?->doc_no ?: '-';
                            $nilai = $penawaran ? $penawaran->calcGrandTotal() : 0;
                            $itemStatus = $item->penawaran_status ?: 'draft';
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold">{{ $docNo }}</td>
                            <td class="px-4 py-3">{{ $penawaran?->nama_pekerjaan ?: $item->judul }}</td>
                            <td class="px-4 py-3">{{ $item->company?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$itemStatus] ?? $statusClasses['draft'] }}">
                                    {{ $statusLabels[$itemStatus] ?? $itemStatus }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('usulan.quotation.show', $item) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">Buka</a>
                                    <a href="{{ route('usulan.quotation.pdf', $item) }}" target="_blank"
                                        data-download-loading data-loading-label="Menyiapkan PDF..." data-download-timeout="30000"
                                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                Belum ada penawaran harga yang diterbitkan.
                                <div class="mt-1 text-xs">Penawaran muncul di sini setelah Anda menanggapi permintaan harga yang masuk.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $quotations->links() }}</div>
@endsection
