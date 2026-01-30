@extends('layouts.app')

@section('content')
    <div class="w-full max-w-2xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Tambah User</h1>
            <p class="text-sm text-slate-500">Buat akun pengguna baru</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    @error('name')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    @error('email')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Password</label>
                        <input type="password" name="password"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                        @error('password')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Role / Jabatan</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-3">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('users.index') }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                <button type="submit"
                    class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Simpan User</button>
            </div>
        </form>
    </div>
@endsection
