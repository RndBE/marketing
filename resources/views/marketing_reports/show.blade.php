@extends('layouts.app', ['title' => 'Detail Laporan Perjalanan Marketing'])

@section('content')
    <div class="flex items-center justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">Detail Laporan Perjalanan Marketing</h1>
            <div class="text-sm text-slate-500 mt-0.5">{{ $report->nomor_laporan ?? '-' }}</div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('marketing-reports.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Kembali</a>
            @if(auth()->user()->hasPermission('edit-marketing-report') && ((int) $report->created_by === (int) auth()->id() || auth()->user()->hasPermission('view-all-marketing-report')))
                <a href="{{ route('marketing-reports.edit', $report->id) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Edit</a>
            @endif
            @if(auth()->user()->hasPermission('delete-marketing-report') && ((int) $report->created_by === (int) auth()->id() || auth()->user()->hasPermission('view-all-marketing-report')))
                <form method="POST" action="{{ route('marketing-reports.destroy', $report->id) }}"
                    onsubmit="return confirm('Hapus laporan ini?')">
                    @csrf
                    @method('DELETE')
                    <button
                        class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-500">Hapus</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-slate-500">Tanggal Pertemuan</div>
                        <div class="font-semibold">{{ $report->tanggal_pertemuan?->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Waktu Pertemuan</div>
                        <div class="font-semibold">{{ $report->waktu_pertemuan ? substr((string) $report->waktu_pertemuan, 0, 5) : '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Tempat Pertemuan</div>
                        <div class="font-semibold">{{ $report->tempat_pertemuan }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Instansi</div>
                        <div class="font-semibold">{{ $report->instansi ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Siapa Saja yang Ditemui</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $report->pihak_ditemui }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Peserta Internal</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $report->peserta_internal ?: '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Isi / Topik Pertemuan</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $report->topik_pembahasan }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Hasil Pertemuan</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $report->hasil_pertemuan ?: '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-2">Rencana Tindak Lanjut</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $report->rencana_tindak_lanjut ?: '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold mb-3">Bukti Pertemuan</div>

                @if($report->attachments->count() === 0)
                    <div class="text-sm text-slate-500">Belum ada lampiran bukti pertemuan.</div>
                @else
                    <div class="space-y-3">
                        @foreach($report->attachments as $attachment)
                            @php
                                $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path);
                                $mime = strtolower((string) $attachment->mime);
                                $isImage = str_contains($mime, 'image');
                                $fileSize = $attachment->size ? number_format($attachment->size / 1024, 1) . ' KB' : '-';
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-3">
                                @if($isImage)
                                    <a href="{{ $fileUrl }}" target="_blank" class="block">
                                        <img src="{{ $fileUrl }}" alt="{{ $attachment->file_name }}"
                                            class="max-h-64 w-auto rounded-lg border border-slate-100">
                                    </a>
                                @endif

                                <div class="mt-2">
                                    <a href="{{ $fileUrl }}" target="_blank"
                                        class="text-sm font-medium text-slate-800 hover:underline">{{ $attachment->file_name }}</a>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $attachment->mime ?: '-' }} • {{ $fileSize }}</div>
                                </div>

                                @if(auth()->user()->hasPermission('edit-marketing-report') && ((int) $report->created_by === (int) auth()->id() || auth()->user()->hasPermission('view-all-marketing-report')))
                                    <form method="POST"
                                        action="{{ route('marketing-reports.attachments.destroy', [$report->id, $attachment->id]) }}"
                                        class="mt-2" onsubmit="return confirm('Hapus lampiran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Hapus Lampiran
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-xs text-slate-500">Status</div>
                <div class="text-xl font-semibold mt-1">
                    @if($report->status === 'draft')
                        Draft
                    @elseif($report->status === 'follow_up')
                        Follow Up
                    @else
                        Selesai
                    @endif
                </div>

                <div class="text-xs text-slate-500 mt-4">Target Tindak Lanjut</div>
                <div class="text-sm font-medium">{{ $report->target_tindak_lanjut?->format('d M Y') ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Dibuat oleh</div>
                <div class="text-sm font-medium">{{ $report->creator->name ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Update terakhir</div>
                <div class="text-sm font-medium">{{ $report->updated_at?->format('d M Y H:i') ?? '-' }}</div>
            </div>
        </div>
    </div>
@endsection
