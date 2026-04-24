@csrf
@if (($method ?? 'POST') !== 'POST')
    @method($method)
@endif

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold mb-1">Kode Perusahaan</label>
            <input type="text" name="code" value="{{ old('code', $company->code) }}"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
            @error('code')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1">Nama Perusahaan</label>
            <input type="text" name="name" value="{{ old('name', $company->name) }}"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
            @error('name')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $company->email) }}"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            @error('email')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1">Telepon</label>
            <input type="text" name="phone" value="{{ old('phone', $company->phone) }}"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            @error('phone')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1">Alamat</label>
        <textarea name="address" rows="4"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('address', $company->address) }}</textarea>
        @error('address')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1">Logo Perusahaan</label>
        @if ($company->logo_path)
            <div class="mb-3 rounded-xl border border-slate-200 bg-slate-50 p-3 inline-block">
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo perusahaan" class="h-20 w-auto">
            </div>
            <label class="mb-3 flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
                <span>Hapus logo saat ini</span>
            </label>
        @endif
        <input type="file" name="logo" accept="image/*"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
        <p class="text-xs text-slate-500 mt-1">Format JPG/PNG/WEBP. Maksimal 2MB. Logo ini dipakai untuk kop PDF per perusahaan.</p>
        @error('logo')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
        @error('remove_logo')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1">Cap Perusahaan</label>
        @if ($company->stamp_path)
            <div class="mb-3 rounded-xl border border-slate-200 bg-slate-50 p-3 inline-block">
                <img src="{{ asset('storage/' . $company->stamp_path) }}" alt="Cap perusahaan" class="h-24 w-auto">
            </div>
            <label class="mb-3 flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remove_stamp" value="1" class="rounded border-slate-300">
                <span>Hapus cap saat ini</span>
            </label>
        @endif
        <input type="file" name="stamp" accept="image/*"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
        <p class="text-xs text-slate-500 mt-1">Format JPG/PNG/WEBP. Maksimal 2MB. Cap ini dipakai di dokumen penawaran sesuai perusahaan aktif.</p>
        @error('stamp')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
        @error('remove_stamp')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="flex justify-end gap-2">
    <a href="{{ route('companies.index') }}"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
    <button type="submit"
        class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">{{ $submitLabel }}</button>
</div>
