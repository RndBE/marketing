@extends('layouts.app')

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold">Manajemen User</h1>
            <a href="{{ route('users.create') }}"
                class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">
                Tambah User
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl">{{ session('error') }}</div>
        @endif

        <div class="mb-4">
            <form action="{{ route('users.index') }}" method="GET">
                <input type="text" name="q" value="{{ $q }}"
                    class="w-full md:w-64 rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Cari user...">
            </form>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Roles</th>
                        <th class="px-4 py-3 text-left">Bergabung</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1 flex-wrap">
                                    @foreach ($user->roles as $role)
                                        <span class="px-2 py-0.5 rounded text-xs bg-slate-100 border border-slate-200">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user->id) }}"
                                        class="px-3 py-1 bg-amber-500 text-white rounded-lg text-xs hover:bg-amber-600">Edit</a>

                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Yakin hapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs hover:bg-red-600">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada user ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
@endsection