@php
    $current = $report ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-semibold mb-1">Tanggal Pertemuan</label>
        <input type="date" name="tanggal_pertemuan"
            value="{{ old('tanggal_pertemuan', optional($current?->tanggal_pertemuan)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
            required
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        @error('tanggal_pertemuan')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">Waktu Pertemuan</label>
        <input type="time" name="waktu_pertemuan"
            value="{{ old('waktu_pertemuan', $current?->waktu_pertemuan ? substr((string) $current->waktu_pertemuan, 0, 5) : '') }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        @error('waktu_pertemuan')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2  gap-4 mt-3">
    <div class="">
        <label class="block text-sm font-semibold mb-1">Tempat Pertemuan</label>
        <input type="text" name="tempat_pertemuan" value="{{ old('tempat_pertemuan', $current?->tempat_pertemuan) }}"
            required placeholder="Contoh: Kantor BBWS Serayu Opak, Yogyakarta"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        @error('tempat_pertemuan')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="">
        <label class="block text-sm font-semibold mb-1">Instansi / User yang Dikunjungi</label>
        <input type="text" name="instansi" value="{{ old('instansi', $current?->instansi) }}"
            placeholder="Contoh: Dinas PU Kabupaten Sleman"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        @error('instansi')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2  gap-4 mt-3">
    <div class="">
        <label class="block text-sm font-semibold mb-1">Siapa Saja yang Ditemui</label>
        <textarea name="pihak_ditemui" rows="3" required
            placeholder="Tuliskan nama, jabatan, dan instansi pihak yang ditemui."
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('pihak_ditemui', $current?->pihak_ditemui) }}</textarea>
        @error('pihak_ditemui')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="">
        <label class="block text-sm font-semibold mb-1">Peserta Internal yang Hadir</label>
        <textarea name="peserta_internal" rows="2"
            placeholder="Contoh: Nofita (Marketing), Akhmad (Business Development)."
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('peserta_internal', $current?->peserta_internal) }}</textarea>
        @error('peserta_internal')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mt-3">
    <label class="block text-sm font-semibold mb-1">Isi / Topik Pertemuan</label>
    <textarea name="topik_pembahasan" rows="4" required
        placeholder="Ringkasan agenda dan hal-hal yang dibahas saat pertemuan."
        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('topik_pembahasan', $current?->topik_pembahasan) }}</textarea>
    @error('topik_pembahasan')
        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mt-3">
    <label class="block text-sm font-semibold mb-1">Hasil Pertemuan</label>
    <textarea name="hasil_pertemuan" rows="3"
        placeholder="Contoh: user meminta revisi spesifikasi / jadwal demo lanjutan."
        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('hasil_pertemuan', $current?->hasil_pertemuan) }}</textarea>
    @error('hasil_pertemuan')
        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mt-3">
    <label class="block text-sm font-semibold mb-1">Rencana Tindak Lanjut</label>
    <textarea name="rencana_tindak_lanjut" rows="3"
        placeholder="Contoh: kirim proposal revisi, jadwalkan presentasi minggu depan."
        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">{{ old('rencana_tindak_lanjut', $current?->rencana_tindak_lanjut) }}</textarea>
    @error('rencana_tindak_lanjut')
        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
    <div>
        <label class="block text-sm font-semibold mb-1">Target Tindak Lanjut</label>
        <input type="date" name="target_tindak_lanjut"
            value="{{ old('target_tindak_lanjut', optional($current?->target_tindak_lanjut)->format('Y-m-d')) }}"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        @error('target_tindak_lanjut')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">Status</label>
        @php
            $currentStatus = old('status', $current?->status ?? 'draft');
        @endphp
        <select name="status"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="follow_up" {{ $currentStatus === 'follow_up' ? 'selected' : '' }}>Follow Up</option>
            <option value="selesai" {{ $currentStatus === 'selesai' ? 'selected' : '' }}>Selesai</option>
        </select>
        @error('status')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mt-4 border-t border-slate-100 pt-4">
    <label class="block text-sm font-semibold mb-1">Bukti Pertemuan (PDF / Gambar)</label>
    <input type="file" name="attachments[]" multiple accept=".pdf,image/*"
        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
    <div class="text-xs text-slate-500 mt-1">Bisa upload lebih dari satu file (pdf, jpg, jpeg, png, webp, gif). Maksimal
        10MB per file.</div>
    @error('attachments')
        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
    @enderror
    @error('attachments.*')
        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
    @enderror

    @if ($current && $current->attachments->count() > 0)
        <div class="mt-3 text-sm font-semibold">Lampiran Saat Ini</div>
        <div class="mt-2 space-y-2">
            @foreach ($current->attachments as $attachment)
                @php
                    $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path);
                    $fileSize = $attachment->size ? number_format($attachment->size / 1024, 1) . ' KB' : '-';
                @endphp
                <div class="rounded-xl border border-slate-200 px-3 py-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ $fileUrl }}" target="_blank"
                            class="text-sm font-medium text-slate-800 hover:underline">
                            {{ $attachment->file_name }}
                        </a>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $attachment->mime ?: '-' }} •
                            {{ $fileSize }}</div>
                    </div>
                    <label class="text-xs text-rose-700 flex items-center gap-2 whitespace-nowrap">
                        <input type="checkbox" name="delete_attachment_ids[]" value="{{ $attachment->id }}"
                            class="rounded">
                        Hapus
                    </label>
                </div>
            @endforeach
        </div>
    @endif
</div>
