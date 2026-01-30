@extends('layouts.app')

@section('content')
    <div class="w-full max-w-4xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold">{{ $usulan->judul }}</h1>
                <p class="text-sm text-slate-500">Diajukan oleh {{ $usulan->creator?->name }} pada
                    {{ $usulan->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <span
                class="px-3 py-1 rounded-lg text-sm bg-{{ $usulan->status_color }}-100 text-{{ $usulan->status_color }}-700">
                {{ $usulan->status_label }}
            </span>
        </div>  
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">PIC/Klien</div>
                <div class="font-medium">{{ $usulan->pic?->instansi ?? '-' }}</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">Nilai Estimasi</div>
                <div class="font-medium">Rp {{ number_format($usulan->nilai_estimasi, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">Deadline</div>
                <div class="font-medium">{{ $usulan->tanggal_dibutuhkan?->format('d/m/Y') ?? '-' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
            <div class="text-xs font-semibold text-slate-500 mb-2">Deskripsi</div>
            <div class="text-sm whitespace-pre-wrap">{{ $usulan->deskripsi ?: '-' }}</div>
        </div>

        @if ($usulan->attachments->count())
            <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
                <div class="text-xs font-semibold text-slate-500 mb-2">Lampiran</div>
                <div class="space-y-2">
                    @foreach ($usulan->attachments as $att)
                        <div class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                            <div>
                                <span class="text-xs bg-slate-200 px-2 py-0.5 rounded mr-2">{{ $att->tipe }}</span>
                                <span class="text-sm">{{ $att->nama_file }}</span>
                            </div>
                            <a href="{{ Storage::url($att->path) }}" target="_blank"
                                class="text-blue-600 text-sm hover:underline">Download</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($usulan->tanggapan)
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-5 mb-4">
                <div class="text-xs font-semibold text-blue-600 mb-2">
                    Tanggapan dari {{ $usulan->responder?->name ?? '-' }}
                    <span class="font-normal text-blue-500">({{ $usulan->tanggal_ditanggapi?->format('d/m/Y H:i') }})</span>
                </div>
                <div class="text-sm whitespace-pre-wrap">{{ $usulan->tanggapan }}</div>
            </div>
        @endif

        @if ($usulan->penawaran_id)
            <div class="bg-green-50 rounded-xl border border-green-200 p-5 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-green-600">Penawaran Sudah Dibuat</div>
                        <div class="text-sm">{{ $usulan->penawaran?->nomor ?? 'Draft' }}</div>
                    </div>
                    <a href="{{ route('penawaran.show', $usulan->penawaran_id) }}"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">Lihat Penawaran</a>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex gap-2 mb-4">
            <a href="{{ route('usulan.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Kembali</a>

            @if ($usulan->status === 'draft')
                <a href="{{ route('usulan.edit', $usulan->id) }}"
                    class="rounded-xl bg-amber-500 text-white px-4 py-2.5 text-sm font-semibold hover:bg-amber-600">Edit</a>
            @endif

            @if (in_array($usulan->status, ['menunggu', 'ditanggapi']) && !$usulan->penawaran_id)
                <button onclick="document.getElementById('tanggapanModal').classList.remove('hidden')"
                    class="rounded-xl bg-blue-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-blue-700">Tanggapi</button>
            @endif

            @if ($usulan->status === 'disetujui' && !$usulan->penawaran_id)
                <form action="{{ route('usulan.buat-penawaran', $usulan->id) }}" method="POST">
                    @csrf
                    <button class="rounded-xl bg-green-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-green-700">
                        Buat Penawaran
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Modal Tanggapi --}}
    <div id="tanggapanModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white w-full max-w-lg rounded-xl p-6" onclick="event.stopPropagation()">
            <h2 class="text-lg font-semibold mb-4">Tanggapi Usulan</h2>
            <form method="POST" action="{{ route('usulan.tanggapi', $usulan->id) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-semibold mb-1">Tanggapan</label>
                    <textarea name="tanggapan" rows="4" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Tuliskan tanggapan atau catatan...">{{ $usulan->tanggapan }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="ditanggapi">Ditanggapi (Perlu Revisi)</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="this.closest('#tanggapanModal').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-200 rounded-xl">Batal</button>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection