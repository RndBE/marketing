@extends('layouts.app', ['title' => 'Buat Penawaran'])

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold">Buat Penawaran</h1>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <form method="POST" action="{{ route('penawaran.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold mb-1">PIC</label>
                <select name="id_pic" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
                    <option value="">Pilih PIC</option>
                    @foreach ($pics as $p)
                        <option value="{{ $p->id }}" @selected(old('id_pic') == $p->id)>
                            {{ $p->nama }}{{ $p->instansi ? ' - ' . $p->instansi : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Judul</label>
                <input name="judul" value="{{ old('judul') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
            </div>


            <div>
                <label class="block text-sm font-semibold mb-1">Catatan</label>
                <textarea name="catatan" rows="4" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">{{ old('catatan') }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('penawaran.index') }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                <button
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button>
            </div>
        </form>
    </div>
@endsection
