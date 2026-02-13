@extends('layouts.app', ['title' => 'Daftar Penawaran'])

@section('content')
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Daftar Penawaran</h1>
        </div>

    </div>

    <form method="GET" class="mb-4">
        <div class="flex gap-2">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Cari..."
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <button
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
            <a href="{{ route('penawaran.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No</th>
                        <th class="px-4 py-3 text-left font-semibold">Judul</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC</th>
                        <th class="px-4 py-3 text-left font-semibold">Updated</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($data as $row)
                        @php
                            $docNo =
                                $row->docNumber?->doc_no ?? 'PNW-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT);
                            $row->approval?->status ?? '-';
                        @endphp
                        @if ($row->approval?->status !== 'dihapus' && $row->approval?->module !== 'penghapusan')
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold">{{ $docNo }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $row->judul ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $row->pic?->nama ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $row->pic?->instansi ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    {{ $row->updated_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $status = $row->approval->status ?? 'draft';
                                        $m = $row->approval->module ?? '';
                                    @endphp

                                    @if ($status === 'menunggu' && $m === 'penawaran')
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Menunggu Approval Disetujui
                                            Step {{ $row->approval->current_step }}
                                        </span>
                                    @elseif ($status === 'disetujui' && $m === 'penawaran')
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Disetujui
                                        </span>
                                    @elseif ($status === 'ditolak' && $m === 'penawaran')
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Ditolak
                                        </span>
                                    @elseif ($status === 'menunggu' && $m === 'penghapusan')
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Menunggu Approval Dihapus
                                            Step {{ $row->approval->current_step }}
                                        </span>
                                    @elseif ($status === 'disetujui' && $m === 'penghapusan')
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Disetujui Dihapus
                                        </span>
                                    @elseif ($status === 'dihapus')
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                            Dihapus
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('penawaran.show', $row->id) }}"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Detail</a>
                                        <a href="{{ route('penawaran.edit', $row->id) }}"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Edit</a>

                                        @php
                                            $status = $row->approval->status ?? '-';
                                            $m = $row->approval->module ?? '';
                                            $stepAktif = $row->approval?->current_step ?? 0;
                                        @endphp
                                        @if ($status === 'draft' || ($status === 'menunggu' && $m === 'penawaran' && $stepAktif == 1))
                                            <form method="POST" action="{{ route('penawaran.destroy', $row->id) }}"
                                                onsubmit="return confirm('Hapus penawaran ini secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                    Hapus
                                                </button>
                                            </form>

                                            {{-- 🔴 PERLU APPROVAL PENGHAPUSAN --}}
                                        @elseif ($status === 'menunggu' && $m === 'penawaran' && $stepAktif >= 2)
                                            <form method="POST" action="{{ route('penawaran.request.delete', $row->id) }}"
                                                onsubmit="return confirm('Ajukan penghapusan penawaran ini?')">
                                                @csrf
                                                <button
                                                    class="rounded-xl border border-rose-300 bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-800 hover:bg-rose-200">
                                                    Ajukan Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif

                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
