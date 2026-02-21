@extends('layouts.app', ['title' => 'Tambah Bundle'])

@section('content')
    <div class="max-w-2xl">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">Tambah Bundle</h1>
        </div>
        <div class="bg-white rounded-xl pb-4 pt-3 px-4  ">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    <div class="font-semibold mb-1">Periksa input berikut:</div>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('price_list.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold mb-1">Kode </label>
                    <input name="kode" value="{{ old('kode') }}"
                        class="w-full rounded-xl border {{ $errors->has('kode') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm"
                        required>
                    @error('kode')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Nama</label>
                    <input name="nama" value="{{ old('nama') }}"
                        class="w-full rounded-xl border {{ $errors->has('nama') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm"
                        required>
                    @error('nama')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Satuan</label>
                    <input name="satuan" value="{{ old('satuan') }}"
                        class="w-full rounded-xl border {{ $errors->has('satuan') ? 'border-rose-400' : 'border-slate-200' }} px-3 py-2 text-sm bg-white"
                        required>
                    @error('satuan')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4"
                        class="w-full rounded-xl border {{ $errors->has('deskripsi') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" type="checkbox" name="is_active" value="1"
                        {{ old('is_active', '1') ? 'checked' : '' }}
                        class="rounded border-slate-300">
                    <label for="is_active" class="text-sm">Aktif</label>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('price_list.index') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
