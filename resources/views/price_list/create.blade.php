@extends('layouts.app', ['title' => 'Tambah Bundle'])

@section('content')
    <div class="max-w-2xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Tambah Bundle</h1>
        </div>

        <form method="POST" action="{{ route('price_list.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold mb-1">Kode </label>
                <input name="kode" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Nama</label>
                <input name="nama" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Satuan</label>
                <input name="satuan" value="{{ old('satuan', $product->satuan ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white" required>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="4" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input id="is_active" type="checkbox" name="is_active" value="1" checked
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
@endsection
