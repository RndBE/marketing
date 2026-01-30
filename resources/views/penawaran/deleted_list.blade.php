@extends('layouts.app', ['title' => 'Riwayat Penghapusan Penawaran'])

@section('content')
    <div class="bg-white rounded-xl shadow p-5">

        <h1 class="text-lg font-semibold mb-4">Riwayat Penawaran Dihapus</h1>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-left">No Dokumen</th>
                        <th class="px-4 py-2 text-left">Judul</th>
                        <th class="px-4 py-2 text-left">Dihapus Oleh</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deleted as $row)
                        <tr class="border-t">
                            <td class="px-4 py-2">
                                {{ $row->penawaran->docNumber->doc_no ?? '-' }}
                            </td>
                            <td class="px-4 py-2">
                                {{ $row->penawaran->judul ?? '-' }}
                            </td>
                            <td class="px-4 py-2">
                                {{ $row->user->name ?? $row->dibuat->name ?? '-' }}
                            </td>
                            <td class="px-4 py-2">
                                {{ $row->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-2 text-slate-600">
                                {{ $row->alasan ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-slate-500">
                                Belum ada penawaran yang dihapus.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $deleted->links() }}
        </div>

    </div>
@endsection
