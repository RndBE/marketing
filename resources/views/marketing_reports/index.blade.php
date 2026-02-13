@extends('layouts.app', ['title' => 'Laporan Perjalanan Marketing'])

@section('content')
    <div class="flex items-center justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Laporan Perjalanan Marketing</h1>
            <div class="text-sm text-slate-500 mt-0.5">Catatan kunjungan dan hasil pertemuan marketing business development.
            </div>
        </div>
        @if(auth()->user()->hasPermission('create-marketing-report'))
            <a href="{{ route('marketing-reports.create') }}"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Buat Laporan Baru
            </a>
        @endif
    </div>

    <form method="GET" class="mb-4 rounded-2xl border border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input name="q" value="{{ $q ?? '' }}"
                placeholder="Cari nomor laporan, tempat, instansi, topik..."
                class="md:col-span-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">

            <select name="status"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                <option value="">Semua Status</option>
                <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="follow_up" {{ ($status ?? '') === 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
        </div>

        <div class="flex gap-2 mt-3">
            <button
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
            <a href="{{ route('marketing-reports.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No Laporan</th>
                        <th class="px-4 py-3 text-left font-semibold">Pertemuan</th>
                        <th class="px-4 py-3 text-left font-semibold">Pihak Ditemui</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Pembuat</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap font-semibold">{{ $row->nomor_laporan ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->tanggal_pertemuan?->format('d M Y') }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $row->tempat_pertemuan }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $row->instansi ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div>{{ \Illuminate\Support\Str::limit($row->pihak_ditemui, 110) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($row->status === 'draft')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">Draft</span>
                                @elseif($row->status === 'follow_up')
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-700">Follow Up</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-700">Selesai</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->creator->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('marketing-reports.show', $row->id) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Detail</a>
                                    @if(auth()->user()->hasPermission('edit-marketing-report') && ((int) $row->created_by === (int) auth()->id() || auth()->user()->hasPermission('view-all-marketing-report')))
                                        <a href="{{ route('marketing-reports.edit', $row->id) }}"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Edit</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('delete-marketing-report') && ((int) $row->created_by === (int) auth()->id() || auth()->user()->hasPermission('view-all-marketing-report')))
                                        <form method="POST" action="{{ route('marketing-reports.destroy', $row->id) }}"
                                            onsubmit="return confirm('Hapus laporan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada laporan perjalanan
                                marketing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
@endsection
