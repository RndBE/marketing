@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold">Kelola Role User</h1>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Roles</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-xs">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" class="px-3 py-1 bg-blue-500 text-white rounded-lg text-xs"
                                    onclick="openRoleModal({{ $user->id }}, '{{ e($user->name) }}', {{ json_encode($user->roles->pluck('id')) }})">
                                    Atur Role
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada user</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Roles --}}
    <div id="roleModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="closeRoleModal(event)">
        <div class="bg-white w-full max-w-md rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-2">Assign Roles</h2>
            <div class="text-sm text-slate-600 mb-4">User: <span id="modal_user_name" class="font-semibold"></span></div>
            <form id="roleForm" method="POST">
                @csrf
                <div class="space-y-2 mb-4">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                class="role-checkbox rounded border-slate-300">
                            <span class="text-sm font-medium">{{ $role->name }}</span>
                            @if ($role->description)
                                <span class="text-xs text-slate-400">— {{ $role->description }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeRoleModal()"
                        class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRoleModal(userId, userName, selectedIds) {
            document.getElementById('roleModal').classList.remove('hidden');
            document.getElementById('modal_user_name').textContent = userName;
            document.getElementById('roleForm').action = `{{ url('/user-roles') }}/${userId}`;
            document.querySelectorAll('.role-checkbox').forEach(cb => {
                cb.checked = selectedIds.includes(parseInt(cb.value));
            });
        }
        function closeRoleModal(e) { if (!e || e.target.id === 'roleModal') document.getElementById('roleModal').classList.add('hidden'); }
    </script>
@endsection