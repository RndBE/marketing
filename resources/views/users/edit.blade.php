@extends('layouts.app')

@section('content')
    <div class="w-full max-w-2xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Edit User</h1>
            <p class="text-sm text-slate-500">Edit data pengguna</p>
        </div>

        <form method="POST" action="{{ route('users.update', $user->id) }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    @error('name')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    @error('email')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-xs text-slate-500 mb-2">Kosongkan jika tidak ingin mengubah password</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Password Baru</label>
                            <input type="password" name="password"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @error('password')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Tanda Tangan (TTD)</label>
                    
                    @if($user->ttd)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $user->ttd) }}" alt="TTD" class="h-24 border border-slate-200 rounded-lg p-2 bg-slate-50">
                        </div>
                    @endif

                    <input type="file" name="ttd" accept="image/*"
                         class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                     <p class="text-xs text-slate-500 mt-1">Upload untuk mengubah TTD. Format: JPG, PNG. Maks: 2MB.</p>
                    @error('ttd')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Role / Jabatan</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-3">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}>
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
                    class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
