@extends('layouts.app')

@section('content')
    <div class="w-full max-w-3xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Edit Usulan Penawaran</h1>
        </div>

        <form method="POST" action="{{ route('usulan.update', $usulan->id) }}" enctype="multipart/form-data"
            class="space-y-4">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Judul Prospek *</label>
                    <input type="text" name="judul" value="{{ old('judul', $usulan->judul) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1">PIC/Klien</label>
                        <select name="pic_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">-- Pilih PIC --</option>
                            @foreach ($pics as $pic)
                                <option value="{{ $pic->id }}" {{ $usulan->pic_id == $pic->id ? 'selected' : '' }}>
                                    {{ $pic->instansi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Nilai Estimasi</label>
                        <input type="number" name="nilai_estimasi"
                            value="{{ old('nilai_estimasi', $usulan->nilai_estimasi) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" min="0">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Tanggal Dibutuhkan</label>
                    <input type="date" name="tanggal_dibutuhkan"
                        value="{{ old('tanggal_dibutuhkan', $usulan->tanggal_dibutuhkan?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Jelaskan detail prospek, kebutuhan klien, dll...">{{ old('deskripsi', $usulan->deskripsi) }}</textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <label class="block text-xs font-semibold mb-2">Lampiran Dokumen</label>

                {{-- Existing attachments --}}
                @if ($usulan->attachments->count())
                    <div class="mb-4 space-y-2">
                        @foreach ($usulan->attachments as $att)
                            <div class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                                <div>
                                    <span class="text-xs bg-slate-200 px-2 py-0.5 rounded mr-2">{{ $att->tipe }}</span>
                                    <span class="text-sm">{{ $att->nama_file }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ Storage::url($att->path) }}" target="_blank"
                                        class="text-blue-600 text-sm hover:underline">Lihat</a>
                                    <button type="button" onclick="deleteAttachment(this, {{ $att->id }})"
                                        class="text-red-500 text-sm hover:underline">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div id="attachment-container">
                    <div class="flex gap-2 mb-2 attachment-row">
                        <select name="attachment_types[]" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="survei">Survei</option>
                            <option value="dokumen">Dokumen</option>
                            <option value="foto">Foto</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <input type="file" name="attachments[]"
                            class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="button" onclick="addAttachmentRow()" class="mt-2 text-sm text-blue-600 hover:underline">+
                    Tambah lampiran</button>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('usulan.show', $usulan->id) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                <button type="submit" name="status" value="draft"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Simpan
                    Draft</button>
                <button type="submit" name="status" value="menunggu"
                    class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Kirim ke
                    Sales</button>
            </div>
        </form>

        <form id="delete-att-form" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
    </div>

    <script>
        function addAttachmentRow() {
            const container = document.getElementById('attachment-container');
            const row = document.createElement('div');
            row.className = 'flex gap-2 mb-2 attachment-row';
            row.innerHTML = `
                        <select name="attachment_types[]" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="survei">Survei</option>
                            <option value="dokumen">Dokumen</option>
                            <option value="foto">Foto</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <input type="file" name="attachments[]" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-sm">Hapus</button>
                    `;
            container.appendChild(row);
        }

        function deleteAttachment(btn, id) {
            if (!confirm('Hapus lampiran ini?')) return;
            const form = document.getElementById('delete-att-form');
            form.action = `{{ url('/usulan/attachment') }}/${id}`;
            form.submit();
        }
    </script>
@endsection