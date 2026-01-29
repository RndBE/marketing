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
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($data as $row)
                        @php $docNo = $row->docNumber?->doc_no ?? ('PNW-' . str_pad((string)$row->id, 6, '0', STR_PAD_LEFT)); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold">{{ $docNo }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->judul ?? '-' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">ID: {{ $row->id }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->pic?->nama ?? '-' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $row->pic?->instansi ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                {{ $row->updated_at?->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('penawaran.show', $row->id) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Detail</a>
                                    <a href="{{ route('penawaran.edit', $row->id) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Edit</a>
                                    <form method="POST" action="{{ route('penawaran.destroy', $row->id) }}"
                                        onsubmit="return confirm('Hapus penawaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- <div class="px-4 py-3 border-t border-slate-200">
            {{ $data->links() }}
        </div> --}}
    </div>
@endsection
