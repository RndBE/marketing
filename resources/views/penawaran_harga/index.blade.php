@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold">Permintaan Harga</h1>
                <p class="mt-1 text-sm text-slate-500">Pisahkan permintaan yang Anda kirim dan yang perlu ditanggapi perusahaan Anda.</p>
            </div>
            <a href="{{ route('penawaran-harga.create') }}"
                class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Buat Permintaan
            </a>
        </div>

        <div class="mb-4 space-y-3 rounded-xl border border-slate-200 bg-white p-3">
            <div class="flex flex-wrap gap-2" aria-label="Filter arah permintaan">
                <a href="{{ route('penawaran-harga.index', array_filter(['status' => $status])) }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold {{ !$direction ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Semua Arah</a>
                <a href="{{ route('penawaran-harga.index', array_filter(['direction' => 'outgoing', 'status' => $status])) }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold {{ $direction === 'outgoing' ? 'bg-violet-600 text-white' : 'bg-violet-50 text-violet-700' }}">Permintaan Keluar</a>
                <a href="{{ route('penawaran-harga.index', array_filter(['direction' => 'incoming', 'status' => $status])) }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold {{ $direction === 'incoming' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700' }}">Permintaan Masuk</a>
            </div>
            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-3" aria-label="Filter status permintaan">
                <a href="{{ route('penawaran-harga.index', array_filter(['direction' => $direction])) }}"
                    class="rounded-lg px-3 py-1.5 text-sm {{ !$status ? 'bg-slate-900 text-white' : 'bg-slate-100' }}">Semua Status</a>
                <a href="{{ route('penawaran-harga.index', array_filter(['direction' => $direction, 'status' => 'menunggu'])) }}"
                    class="rounded-lg px-3 py-1.5 text-sm {{ $status === 'menunggu' ? 'bg-amber-500 text-white' : 'bg-slate-100' }}">Menunggu</a>
                <a href="{{ route('penawaran-harga.index', array_filter(['direction' => $direction, 'status' => 'disetujui'])) }}"
                    class="rounded-lg px-3 py-1.5 text-sm {{ $status === 'disetujui' ? 'bg-green-500 text-white' : 'bg-slate-100' }}">Ditanggapi</a>
                <a href="{{ route('penawaran-harga.index', array_filter(['direction' => $direction, 'status' => 'ditolak'])) }}"
                    class="rounded-lg px-3 py-1.5 text-sm {{ $status === 'ditolak' ? 'bg-red-500 text-white' : 'bg-slate-100' }}">Ditolak</a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Judul</th>
                        <th class="px-4 py-3 text-left">Dari → Kepada</th>
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
                            <td class="px-4 py-3 text-slate-600">
                                @if((int) $u->company_id === (int) $companyId)
                                    <span class="mb-1 inline-flex rounded bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-700">Keluar · Anda pembeli</span>
                                @else
                                    <span class="mb-1 inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Masuk · Anda penjual</span>
                                @endif
                                <div>{{ $u->company?->code ?? $u->company?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">→ {{ $u->targetCompany?->code ?? $u->targetCompany?->name ?? 'Legacy/semua' }}</div>
                            </td>
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
                                        <a data-primary-action="detail" href="{{ route('penawaran-harga.show', $u->id) }}"
                                            class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                                            Lihat
                                        </a>
                                    </x-slot:primary>

                                    @if ($u->penawaran_id && ((int) $u->target_company_id === (int) $companyId || $u->penawaran_status !== 'draft'))
                                        <a href="{{ route('penawaran-harga.quotation.show', $u) }}"
                                            class="rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                            Lihat Penawaran Harga
                                        </a>
                                    @endif
                                    @if ((int) $u->company_id === (int) $companyId && in_array($u->status, ['draft', 'ditolak']))
                                        <form action="{{ route('penawaran-harga.destroy', $u->id) }}" method="POST"
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
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                <div class="font-semibold text-slate-700">Tidak ada permintaan pada filter ini</div>
                                <div class="mt-1 text-xs">Ubah filter arah/status atau buat permintaan harga baru.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-4">{{ $usulan->links() }}</div>
    </div>
@endsection
