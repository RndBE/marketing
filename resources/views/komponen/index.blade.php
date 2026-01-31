@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold">Komponen Produk</h1>
            <div class="flex gap-2">
                <button onclick="openImportModal()"
                    class="rounded-xl bg-white  border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold hover:bg-slate-200">
                    Import CSV
                </button>
                <button onclick="openCreateModal()"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    Tambah Komponen
                </button>
            </div>
        </div>

        <form method="GET" class="mb-4">
            <div class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari komponen..."
                    class="flex-1 rounded-xl border border-slate-300 focus:border-slate-500 focus:ring-slate-500 px-3 py-2 text-sm">
                <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm">Cari</button>
            </div>
        </form>

        <form id="bulkDeleteForm" action="{{ route('komponen.bulk-delete') }}" method="POST" class="mb-4">
            @csrf
            @method('DELETE')
            <div id="bulkActions" class="hidden bg-red-50 border border-red-200 rounded-xl p-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-red-900">
                    <span id="selectedCount">0</span> item dipilih
                </span>
                <button type="submit" onclick="return confirm('Hapus semua item yang dipilih?')"
                    class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-700">
                    Hapus Terpilih
                </button>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">
                            <input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)"
                                class="rounded border-slate-300">
                        </th>
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
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $k->id }}" 
                                    class="item-checkbox rounded border-slate-300" onchange="updateBulkActions()">
                            </td>
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
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada komponen</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $komponen->links() }}</div>
    </div>

    <div id="importModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        onclick="closeImportModal(event)">
        <div class="bg-white w-full max-w-lg rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Import Komponen dari CSV</h2>
            <form action="{{ route('komponen.bulk-import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">File CSV</label>
                        <input type="file" name="csv_file" accept=".csv" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                        <p class="text-xs text-slate-500 mt-1">
                            Format CSV: nama, kode, satuan, harga<br>
                            Contoh: "Datalogger BL-1100", "BL-1100", "Unit", "56,194,737"
                        </p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm">
                        <p class="font-semibold text-blue-900 mb-1">📝 Catatan:</p>
                        <ul class="text-blue-800 space-y-1 text-xs list-disc pl-4">
                            <li>File CSV harus memiliki header di baris pertama</li>
                            <li>Harga bisa pakai pemisah ribuan (koma)</li>
                            <li>Komponen dengan kode yang sama akan diupdate</li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeImportModal()"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm">
                        Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="createModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeCreateModal(event)">
        <div class="bg-white w-full max-w-xl rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Tambah Komponen</h2>
            <form method="POST" action="{{ route('komponen.store') }}">
                @csrf
                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-semibold mb-1">Kode</label>
                        <input type="text" name="kode" class="w-full border border-slate-300     rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-semibold mb-1">Nama *</label>
                        <input type="text" name="nama" class="w-full border border-slate-300     rounded-xl px-3 py-2 text-sm" required>
                    </div>
                    <div class="col-span-4">
                        <label class="block text-sm font-semibold mb-1">Spesifikasi</label>
                        <textarea name="spesifikasi" rows="2" class="w-full border border-slate-300  rounded-xl px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-sm font-semibold mb-1">Satuan</label>
                        <input type="text" name="satuan" class="w-full border border-slate-300   rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-semibold mb-1">Harga *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-sm text-slate-500">Rp</span>
                            <input type="text" id="create_display_harga" class="w-full border border-slate-300   rounded-xl pl-9 pr-3 py-2 text-sm" required
                                onkeyup="handlePriceInput(this, 'create_harga')">
                            <input type="hidden" name="harga" id="create_harga">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeEditModal(event)">
        <div class="bg-white w-full max-w-xl rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Edit Komponen</h2>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-semibold mb-1">Kode</label>
                        <input type="text" name="kode" id="edit_kode" class="w-full border-slate-300 border rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-semibold mb-1">Nama *</label>
                        <input type="text" name="nama" id="edit_nama" class="w-full border-slate-300 border rounded-xl px-3 py-2 text-sm" required>
                    </div>
                    <div class="col-span-4">
                        <label class="block text-sm font-semibold mb-1">Spesifikasi</label>
                        <textarea name="spesifikasi" id="edit_spesifikasi" rows="2" class="w-full border-slate-300 border rounded-xl px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-sm font-semibold mb-1">Satuan</label>
                        <input type="text" name="satuan" id="edit_satuan" class="w-full border-slate-300 border rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-semibold mb-1">Harga *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-sm text-slate-500">Rp</span>
                            <input type="text" id="edit_display_harga" class="w-full border-slate-300 border rounded-xl pl-9 pr-3 py-2 text-sm" required
                                onkeyup="handlePriceInput(this, 'edit_harga')">
                            <input type="hidden" name="harga" id="edit_harga">
                        </div>
                    </div>
                    <div class="col-span-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border border-slate-300    ">
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

        function openImportModal() { document.getElementById('importModal').classList.remove('hidden'); }
        function closeImportModal(e) { if (!e || e.target.id === 'importModal') document.getElementById('importModal').classList.add('hidden'); }

        function openEditModal(btn) {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('edit_kode').value = btn.dataset.kode || '';
            document.getElementById('edit_nama').value = btn.dataset.nama;
            document.getElementById('edit_spesifikasi').value = btn.dataset.spesifikasi || '';
            document.getElementById('edit_satuan').value = btn.dataset.satuan || '';
            
           
            const harga = btn.dataset.harga;
            document.getElementById('edit_harga').value = harga;
            document.getElementById('edit_display_harga').value = formatRupiah(harga);

            document.getElementById('edit_is_active').checked = btn.dataset.is_active === '1';
            document.getElementById('editForm').action = `{{ url('/komponen') }}/${btn.dataset.id}`;
        }
        function closeEditModal(e) { if (!e || e.target.id === 'editModal') document.getElementById('editModal').classList.add('hidden'); }
      
        function handlePriceInput(input, targetId) {
            let value = input.value.replace(/\D/g, '');
            document.getElementById(targetId).value = value;
            input.value = formatRupiah(value);
        }

        function formatRupiah(angka) {
            if (!angka) return '';
            angka = angka.toString();
            let number_string = angka.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return rupiah;
        }

       
        function toggleCheckAll(checkbox) {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const count = checkboxes.length;
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            const checkAll = document.getElementById('checkAll');
            
            selectedCount.textContent = count;
            
            if (count > 0) {
                bulkActions.classList.remove('hidden');
            } else {
                bulkActions.classList.add('hidden');
            }
           
            const allCheckboxes = document.querySelectorAll('.item-checkbox');
            checkAll.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
        }
    </script>
@endsection
