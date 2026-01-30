@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold">Manajemen Role</h1>
            <button onclick="openCreateModal()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Tambah Role
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
                        <th class="px-4 py-3 text-left">Deskripsi</th>
                        <th class="px-4 py-3 text-left">Permissions</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($roles as $role)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $role->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-slate-100 rounded text-xs">{{ $role->slug }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $role->description ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($role->permissions as $perm)
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">{{ $perm->name }}</span>
                                    @empty
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" class="px-3 py-1 bg-blue-500 text-white rounded-lg text-xs"
                                    onclick="openPermissionModal({{ $role->id }}, '{{ e($role->name) }}', {{ json_encode($role->permissions->pluck('id')) }})">
                                    Permissions
                                </button>
                                <button type="button" class="px-3 py-1 bg-amber-500 text-white rounded-lg text-xs"
                                    data-id="{{ $role->id }}" data-name="{{ e($role->name) }}" data-slug="{{ e($role->slug) }}"
                                    data-description="{{ e($role->description) }}" onclick="openEditModal(this)">
                                    Edit
                                </button>
                                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Hapus role ini?')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada role</td>
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
            <h2 class="text-lg font-semibold mb-4">Tambah Role</h2>
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Nama</label>
                    <input type="text" name="name" class="w-full border rounded-xl px-3 py-2 text-sm" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-semibold mb-1">Slug</label>
                    <input type="text" name="slug" class="w-full border rounded-xl px-3 py-2 text-sm" required>
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
            <h2 class="text-lg font-semibold mb-4">Edit Role</h2>
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

    {{-- Modal Permissions --}}
    <div id="permissionModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closePermissionModal(event)">
        <div class="bg-white w-full max-w-lg rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-2">Assign Permissions</h2>
            <div class="text-sm text-slate-600 mb-4">Role: <span id="perm_role_name" class="font-semibold"></span></div>
            <form id="permissionForm" method="POST">
                @csrf
                <div class="max-h-64 overflow-y-auto space-y-2 mb-4">
                    @foreach ($permissions as $perm)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                class="perm-checkbox rounded border-slate-300">
                            <span class="text-sm">{{ $perm->name }}</span>
                            @if ($perm->group)
                                <span class="text-xs text-slate-400">({{ $perm->group }})</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closePermissionModal()"
                        class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Simpan</button>
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
            document.getElementById('edit_description').value = btn.dataset.description || '';
            document.getElementById('editForm').action = `{{ url('/roles') }}/${btn.dataset.id}`;
        }
        function closeEditModal(e) { if (!e || e.target.id === 'editModal') document.getElementById('editModal').classList.add('hidden'); }

        function openPermissionModal(roleId, roleName, selectedIds) {
            document.getElementById('permissionModal').classList.remove('hidden');
            document.getElementById('perm_role_name').textContent = roleName;
            document.getElementById('permissionForm').action = `{{ url('/roles') }}/${roleId}/permissions`;
            document.querySelectorAll('.perm-checkbox').forEach(cb => {
                cb.checked = selectedIds.includes(parseInt(cb.value));
            });
        }
        function closePermissionModal(e) { if (!e || e.target.id === 'permissionModal') document.getElementById('permissionModal').classList.add('hidden'); }
    </script>
@endsection