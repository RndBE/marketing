@php
    $signatureUsulan = $usulan ?? null;
    $defaultPosition = auth()->user()?->roles?->pluck('name')->implode(', ') ?: '';
    $signatureName = old('signature_name', $signatureUsulan?->signature_name ?: auth()->user()?->name);
    $signaturePosition = old('signature_position', $signatureUsulan?->signature_position ?: $defaultPosition);
    $signatureCity = old('signature_city', $signatureUsulan?->signature_city ?: 'Yogyakarta');
    $signatureDate = old(
        'signature_date',
        optional($signatureUsulan?->signature_date ?: now())->format('Y-m-d')
    );
@endphp

<div class="rounded-xl border border-violet-200 bg-violet-50/50 p-5">
    <div class="mb-4">
        <h2 class="text-sm font-semibold text-slate-900">Tanda Tangan Permohonan Penawaran</h2>
        <p class="mt-1 text-xs text-slate-500">Opsional. TTD ini hanya digunakan pada PDF Permohonan Penawaran.</p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-semibold">Nama penanda tangan</label>
            <input name="signature_name" value="{{ $signatureName }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                placeholder="Nama penanda tangan">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold">Jabatan</label>
            <input name="signature_position" value="{{ $signaturePosition }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                placeholder="Contoh: Corporate Account Manager">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold">Kota</label>
            <input name="signature_city" value="{{ $signatureCity }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold">Tanggal tanda tangan</label>
            <input type="date" name="signature_date" value="{{ $signatureDate }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        </div>
    </div>

    @if($signatureUsulan?->signature_path)
        <div class="mt-3">
            <div class="mb-1 text-xs font-semibold">TTD saat ini</div>
            <img src="{{ asset('storage/'.$signatureUsulan->signature_path) }}" alt="TTD Permohonan"
                class="h-20 rounded-lg border border-slate-200 bg-white p-2">
        </div>
    @endif

    <div class="mt-3">
        <label class="mb-1 block text-xs font-semibold">
            {{ $signatureUsulan?->signature_path ? 'Import TTD pengganti (opsional)' : 'Import / Upload TTD (opsional)' }}
        </label>
        <input type="file" name="signature_file" accept="image/png,image/jpeg,image/webp"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-violet-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-violet-700">
        <p class="mt-1 text-xs text-slate-500">PNG transparan lebih disarankan. Format JPG, PNG, atau WebP; maksimal 2 MB.</p>
    </div>
</div>
