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
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-table-actions scope="usulan" menu-label="Buka aksi usulan"
                                    :has-menu="(bool) $u->penawaran_id || in_array($u->status, ['draft', 'ditolak'])">
                                    <x-slot:primary>
                                        <a data-primary-action="detail" href="{{ route('usulan.show', $u->id) }}"
                                            class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                                            Lihat
                                        </a>
                                    </x-slot:primary>

                                    @if ($u->penawaran_id)
                                        <a href="{{ route('penawaran.show', $u->penawaran_id) }}"
                                            class="rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                            Lihat Penawaran
                                        </a>
                                    @endif
                                    @if (in_array($u->status, ['draft', 'ditolak']))
                                        <form action="{{ route('usulan.destroy', $u->id) }}" method="POST"
                                            data-confirm-title="Hapus Usulan?"
                                            data-confirm-delete="Usulan {{ $u->judul }} akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-full rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </x-table-actions>
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
