@extends('layouts.app', ['title' => 'Daftar Penawaran'])

@section('content')
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Daftar Penawaran</h1>
        </div>

    </div>

    <form method="GET" class="mb-4" id="filter-form">
        <div class="flex flex-wrap gap-2">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Cari..."
                class="flex-1 min-w-[160px] rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">

            {{-- Date range --}}
            <div class="flex items-center gap-1">
                <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                <span class="text-slate-400 text-sm">–</span>
                <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>

            <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
            <a href="{{ route('penawaran.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>

        {{-- Shortcuts --}}
        @php
            $y = date('Y'); $m = date('m');
        @endphp
        <div class="flex flex-wrap gap-2 mt-2">
            <span class="text-xs text-slate-500 self-center">Cepat:</span>
            <button type="button" onclick="setRange('{{ $y }}-01-01','{{ $y }}-12-31')"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold hover:bg-slate-50 {{ ($dateFrom === "{$y}-01-01" && $dateTo === "{$y}-12-31") ? 'bg-slate-900 text-black border-slate-900' : '' }}">
                Tahun {{ $y }}
            </button>
            @for($yr = (int)$y - 1; $yr >= (int)$y - 2; $yr--)
            <button type="button" onclick="setRange('{{ $yr }}-01-01','{{ $yr }}-12-31')"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold hover:bg-slate-50 {{ ($dateFrom === "{$yr}-01-01" && $dateTo === "{$yr}-12-31") ? 'bg-slate-900 text-white border-slate-900' : '' }}">
                {{ $yr }}
            </button>
            @endfor
            <button type="button" onclick="setRange('{{ $y }}-{{ $m }}-01','{{ $y }}-{{ $m }}-31')"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold hover:bg-slate-50">
                Bulan Ini
            </button>
            <button type="button" onclick="setRange('2000-01-01','2099-12-31')"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold hover:bg-slate-50 {{ ($dateTo === '2099-12-31') ? 'bg-slate-900 text-white border-slate-900' : '' }}">
                Semua Waktu
            </button>
        </div>
    </form>

    <script>
    function setRange(from, to) {
        document.getElementById('date_from').value = from;
        document.getElementById('date_to').value = to;
        document.getElementById('filter-form').submit();
    }
    </script>

    {{-- ── Dashboard Ringkasan ── --}}
    @if ($jumlahDisetujui > 0 || $jumlahGoal > 0)
        @php
            $rangeLabel = \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
        @endphp
        <div class="mb-1 text-xs text-slate-500 font-medium">Periode: {{ $rangeLabel }}</div>
        <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Penawaran Disetujui --}}
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-green-100 shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-xs font-semibold text-green-700 uppercase tracking-wide">Disetujui</div>
                </div>
                <div class="text-2xl font-bold text-green-900">{{ $jumlahDisetujui }}
                    <span class="text-sm font-normal text-green-700">penawaran</span>
                </div>
                <div class="mt-1 text-sm font-semibold text-green-800">
                    Rp {{ number_format($totalDisetujui, 0, ',', '.') }}
                </div>
            </div>

            {{-- Penawaran Goal --}}
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 shrink-0 text-base">
                        🏆
                    </div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Goal / Project</div>
                </div>
                <div class="text-2xl font-bold text-blue-900">{{ $jumlahGoal }}
                    <span class="text-sm font-normal text-blue-700">penawaran</span>
                </div>
                <div class="mt-1 text-sm font-semibold text-blue-800">
                    Rp {{ number_format($totalGoal, 0, ',', '.') }}
                </div>
            </div>

            {{-- Konversi --}}
            <div class="rounded-2xl border border-purple-200 bg-purple-50 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-purple-100 shrink-0">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="text-xs font-semibold text-purple-700 uppercase tracking-wide">Konversi ke Goal</div>
                </div>

                {{-- Konversi Jumlah --}}
                <div class="mb-2">
                    <div class="flex items-center justify-between text-xs text-purple-700 mb-1">
                        <span>Jumlah</span>
                        <span class="font-bold">{{ $pctJumlah }}%</span>
                    </div>
                    <div class="w-full bg-purple-100 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full"
                             style="width: {{ min($pctJumlah, 100) }}%"></div>
                    </div>
                    <div class="text-[11px] text-purple-600 mt-0.5">{{ $jumlahGoal }} dari {{ $jumlahDisetujui }} penawaran</div>
                </div>

                {{-- Konversi Nilai --}}
                <div>
                    <div class="flex items-center justify-between text-xs text-purple-700 mb-1">
                        <span>Nilai</span>
                        <span class="font-bold">{{ $pctNilai }}%</span>
                    </div>
                    <div class="w-full bg-purple-100 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full"
                             style="width: {{ min($pctNilai, 100) }}%"></div>
                    </div>
                    <div class="text-[11px] text-purple-600 mt-0.5">
                        Rp {{ number_format($totalGoal, 0, ',', '.') }} dari Rp {{ number_format($totalDisetujui, 0, ',', '.') }}
                    </div>
                </div>
            </div>

        </div>
    @endif

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
                                    <div class="font-medium">{{ trim(($row->pic?->honorific ? $row->pic->honorific . ' ' : '') . ($row->pic?->nama ?? '-')) }}</div>
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

                                    @if ($row->is_goal)
                                        <span class="ml-1 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            🏆 Goal
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

                                        @if(auth()->user()->hasPermission('create-penawaran'))
                                            <form method="POST" action="{{ route('penawaran.duplicate', $row->id) }}"
                                                onsubmit="return confirm('Duplikat penawaran ini?')">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                                    ⧉ Duplikat
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif

                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $data->links() }}
    </div>
@endsection
