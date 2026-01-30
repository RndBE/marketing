@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold">Komponen Produk</h1>
            <button onclick="openCreateModal()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Tambah Komponen
            </button>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl">{{ session('success') }}</div>
        @endif

        {{-- Search --}}
        <form method="GET" class="mb-4">
            <div class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari komponen..."
                    class="flex-1 rounded-xl border px-3 py-2 text-sm">
                <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm">Cari</button>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($komponen as $k)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($k->kode)
                                    <span class="px-2 py-1 bg-slate-100 rounded text-xs">{{ $k->kode }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $k->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $k->satuan ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($k->harga, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($k->is_active)
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 bg-slate-200 text-slate-600 rounded text-xs">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" class="px-3 py-1 bg-amber-500 text-white rounded-lg text-xs"
                                    data-id="{{ $k->id }}" data-kode="{{ e($k->kode) }}"
                                    data-nama="{{ e($k->nama) }}" data-spesifikasi="{{ e($k->spesifikasi) }}"
                                    data-satuan="{{ e($k->satuan) }}" data-harga="{{ $k->harga }}"
                                    data-is_active="{{ $k->is_active ? '1' : '0' }}"
                                    onclick="openEditModal(this)">
                                    Edit
                                </button>
                                <form action="{{ route('komponen.destroy', $k->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Hapus komponen ini?')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada komponen</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $komponen->links() }}</div>
    </div>

    {{-- Modal Create --}}
    <div id="createModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeCreateModal(event)">
        <div class="bg-white w-full max-w-lg rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Tambah Komponen</h2>
            <form method="POST" action="{{ route('komponen.store') }}">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Kode</label>
                        <input type="text" name="kode" class="w-full border rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nama *</label>
                        <input type="text" name="nama" class="w-full border rounded-xl px-3 py-2 text-sm" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1">Spesifikasi</label>
                        <textarea name="spesifikasi" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Satuan</label>
                        <input type="text" name="satuan" class="w-full border rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Harga *</label>
                        <input type="number" name="harga" class="w-full border rounded-xl px-3 py-2 text-sm" required min="0">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div id="editModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeEditModal(event)">
        <div class="bg-white w-full max-w-lg rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Edit Komponen</h2>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Kode</label>
                        <input type="text" name="kode" id="edit_kode" class="w-full border rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nama *</label>
                        <input type="text" name="nama" id="edit_nama" class="w-full border rounded-xl px-3 py-2 text-sm" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1">Spesifikasi</label>
                        <textarea name="spesifikasi" id="edit_spesifikasi" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Satuan</label>
                        <input type="text" name="satuan" id="edit_satuan" class="w-full border rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Harga *</label>
                        <input type="number" name="harga" id="edit_harga" class="w-full border rounded-xl px-3 py-2 text-sm" required min="0">
                    </div>
                    <div class="col-span-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-slate-300">
                            <span class="text-sm">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() { document.getElementById('createModal').classList.remove('hidden'); }
        function closeCreateModal(e) { if (!e || e.target.id === 'createModal') document.getElementById('createModal').classList.add('hidden'); }

        function openEditModal(btn) {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('edit_kode').value = btn.dataset.kode || '';
            document.getElementById('edit_nama').value = btn.dataset.nama;
            document.getElementById('edit_spesifikasi').value = btn.dataset.spesifikasi || '';
            document.getElementById('edit_satuan').value = btn.dataset.satuan || '';
            document.getElementById('edit_harga').value = btn.dataset.harga;
            document.getElementById('edit_is_active').checked = btn.dataset.is_active === '1';
            document.getElementById('editForm').action = `{{ url('/komponen') }}/${btn.dataset.id}`;
        }
        function closeEditModal(e) { if (!e || e.target.id === 'editModal') document.getElementById('editModal').classList.add('hidden'); }
    </script>
@endsection
