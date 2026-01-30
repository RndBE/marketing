@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold">Usulan Penawaran</h1>
            <a href="{{ route('usulan.create') }}"
                class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">
                Buat Usulan
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl">{{ session('success') }}</div>
        @endif

        {{-- Filter --}}
        <div class="flex gap-2 mb-4">
            <a href="{{ route('usulan.index') }}"
                class="px-3 py-1.5 rounded-lg text-sm {{ !$status ? 'bg-slate-900 text-white' : 'bg-slate-100' }}">Semua</a>
            <a href="{{ route('usulan.index', ['status' => 'menunggu']) }}"
                class="px-3 py-1.5 rounded-lg text-sm {{ $status === 'menunggu' ? 'bg-amber-500 text-white' : 'bg-slate-100' }}">Menunggu</a>
            <a href="{{ route('usulan.index', ['status' => 'disetujui']) }}"
                class="px-3 py-1.5 rounded-lg text-sm {{ $status === 'disetujui' ? 'bg-green-500 text-white' : 'bg-slate-100' }}">Disetujui</a>
            <a href="{{ route('usulan.index', ['status' => 'ditolak']) }}"
                class="px-3 py-1.5 rounded-lg text-sm {{ $status === 'ditolak' ? 'bg-red-500 text-white' : 'bg-slate-100' }}">Ditolak</a>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Judul</th>
                        <th class="px-4 py-3 text-left">PIC/Klien</th>
                        <th class="px-4 py-3 text-right">Estimasi</th>
                        <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-left">Deadline</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($usulan as $u)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $u->judul }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $u->pic?->instansi ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($u->nilai_estimasi, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $u->creator?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs bg-{{ $u->status_color }}-100 text-{{ $u->status_color }}-700">
                                    {{ $u->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $u->tanggal_dibutuhkan?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('usulan.show', $u->id) }}"
                                    class="px-3 py-1 bg-slate-900 text-white rounded-lg text-xs">Lihat</a>
                                @if ($u->penawaran_id)
                                    <a href="{{ route('penawaran.show', $u->penawaran_id) }}"
                                        class="px-3 py-1 bg-green-600 text-white rounded-lg text-xs">Penawaran</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada usulan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $usulan->links() }}</div>
    </div>
@endsection