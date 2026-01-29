@extends('layouts.app', ['title' => 'Price List'])

@section('content')
    <div class="flex items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Price List</h1>
            <div class="text-sm text-slate-500">Bundle (products) dan item di dalamnya (product_details)</div>
        </div>
        <a href="{{ route('price_list.create') }}"
            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Tambah Bundle</a>
    </div>

    <form class="mb-4" method="GET">
        <div class="flex gap-2">
            <input name="q" value="{{ $q }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                placeholder="Cari kode/nama...">
            <button
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Cari</button>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Kode</th>
                    <th class="px-4 py-3 text-left font-semibold">Nama</th>
                    <th class="px-4 py-3 text-center font-semibold">Satuan</th>
                    <th class="px-4 py-3 text-center font-semibold">Aktif</th>
                    {{-- <th class="px-4 py-3 text-center font-semibold">Detail</th> --}}
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($data as $p)
                    <tr>
                        <td class="px-4 py-3">{{ $p->kode ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('price_list.show', $p->id) }}"
                                class="font-semibold hover:underline">{{ $p->nama }}</a>
                            @if ($p->deskripsi)
                                <div class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $p->deskripsi }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">{{ $p->satuan }}</td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $p->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        {{-- <td class="px-4 py-3 text-center">{{ $p->details_count }}</td> --}}
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('price_list.edit', $p->id) }}"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Edit</a>
                            <form action="{{ route('price_list.destroy', $p->id) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Yakin mau hapus bundle ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">
                                    Hapus
                                </button>
                            </form>
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

    <div class="mt-4">
        {{ $data->links() }}
    </div>
@endsection
