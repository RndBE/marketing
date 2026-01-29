@csrf
@if (isset($pic))
    @method('PUT')
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="text-xs font-semibold">Nama</label>
        <input name="nama" value="{{ old('nama', $pic->nama ?? '') }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="text-xs font-semibold">Jabatan</label>
        <input name="jabatan" value="{{ old('jabatan', $pic->jabatan ?? '') }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="text-xs font-semibold">Instansi</label>
        <input name="instansi" value="{{ old('instansi', $pic->instansi ?? '') }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="text-xs font-semibold">Email</label>
        <input name="email" value="{{ old('email', $pic->email ?? '') }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="text-xs font-semibold">No HP</label>
        <input name="no_hp" value="{{ old('no_hp', $pic->no_hp ?? '') }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div class="md:col-span-2">
        <label class="text-xs font-semibold">Alamat</label>
        <textarea name="alamat" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('alamat', $pic->alamat ?? '') }}</textarea>
    </div>
</div>

<div class="mt-4 text-right">
    <button class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
        Simpan
    </button>
</div>
