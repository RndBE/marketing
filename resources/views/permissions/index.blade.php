@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold">Manajemen Permission</h1>
            <button onclick="openCreateModal()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Tambah Permission
            </button>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Slug</th>
                        <th class="px-4 py-3 text-left">Group</th>
                        <th class="px-4 py-3 text-left">Deskripsi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($permissions as $perm)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $perm->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-slate-100 rounded text-xs">{{ $perm->slug }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($perm->group)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ $perm->group }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $perm->description ?? '-' }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" class="px-3 py-1 bg-amber-500 text-white rounded-lg text-xs"
                                    data-id="{{ $perm->id }}" data-name="{{ e($perm->name) }}" data-slug="{{ e($perm->slug) }}"
                                    data-group="{{ e($perm->group) }}" data-description="{{ e($perm->description) }}"
                                    onclick="openEditModal(this)">
                                    Edit
                                </button>
                                <form action="{{ route('permissions.destroy', $perm->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Hapus permission ini?')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada permission</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Create --}}
    <div id="createModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeCreateModal(event)">
        <div class="bg-white w-full max-w-md rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Tambah Permission</h2>
            <form method="POST" action="{{ route('permissions.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Nama</label>
                    <input type="text" name="name" class="w-full border rounded-xl px-3 py-2 text-sm" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Slug</label>
                    <input type="text" name="slug" class="w-full border rounded-xl px-3 py-2 text-sm" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Group</label>
                    <input type="text" name="group" class="w-full border rounded-xl px-3 py-2 text-sm" list="groupList">
                    <datalist id="groupList">
                        @foreach ($groups as $g)
                            <option value="{{ $g }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Deskripsi</label>
                    <input type="text" name="description" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div id="editModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeEditModal(event)">
        <div class="bg-white w-full max-w-md rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Edit Permission</h2>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Nama</label>
                    <input type="text" name="name" id="edit_name" class="w-full border rounded-xl px-3 py-2 text-sm"
                        required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Slug</label>
                    <input type="text" name="slug" id="edit_slug" class="w-full border rounded-xl px-3 py-2 text-sm"
                        required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Group</label>
                    <input type="text" name="group" id="edit_group" class="w-full border rounded-xl px-3 py-2 text-sm"
                        list="groupListEdit">
                    <datalist id="groupListEdit">
                        @foreach ($groups as $g)
                            <option value="{{ $g }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Deskripsi</label>
                    <input type="text" name="description" id="edit_description"
                        class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
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
            document.getElementById('edit_name').value = btn.dataset.name;
            document.getElementById('edit_slug').value = btn.dataset.slug;
            document.getElementById('edit_group').value = btn.dataset.group || '';
            document.getElementById('edit_description').value = btn.dataset.description || '';
            document.getElementById('editForm').action = `{{ url('/permissions') }}/${btn.dataset.id}`;
        }
        function closeEditModal(e) { if (!e || e.target.id === 'editModal') document.getElementById('editModal').classList.add('hidden'); }
    </script>
@endsection