@extends('layouts.app', ['title' => 'Detail Permintaan Harga'])

@section('content')
    @php
        $quotationLabels = [
            'none' => 'Belum dibuat',
            'draft' => 'Draft penawaran',
            'sent' => 'Menunggu keputusan pembeli',
            'accepted' => 'Penawaran disetujui',
            'revision_requested' => 'Revisi diminta',
            'rejected' => 'Penawaran ditolak',
        ];
        $quotationClasses = [
            'none' => 'bg-slate-100 text-slate-700',
            'draft' => 'bg-slate-100 text-slate-700',
            'sent' => 'bg-blue-100 text-blue-700',
            'accepted' => 'bg-emerald-100 text-emerald-700',
            'revision_requested' => 'bg-amber-100 text-amber-800',
            'rejected' => 'bg-red-100 text-red-700',
        ];
        $stepClasses = [
            'complete' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'current' => 'border-blue-300 bg-blue-50 text-blue-800 ring-2 ring-blue-100',
            'warning' => 'border-amber-300 bg-amber-50 text-amber-900 ring-2 ring-amber-100',
            'danger' => 'border-red-300 bg-red-50 text-red-800',
            'pending' => 'border-slate-200 bg-white text-slate-600',
        ];
        $requestStepLabel = match($usulan->status) {
            'draft' => 'Draft, belum dikirim',
            'menunggu' => 'Menunggu tanggapan penjual',
            'ditanggapi', 'disetujui' => 'Sudah ditanggapi penjual',
            'ditolak' => 'Ditolak penjual',
            default => $usulan->status_label,
        };
        $requestStepTone = match($usulan->status) {
            'draft', 'menunggu' => 'current',
            'ditolak' => 'danger',
            default => 'complete',
        };
        $quotationStepTone = match($usulan->penawaran_status) {
            'accepted' => 'complete',
            'sent' => 'current',
            'draft', 'revision_requested' => 'warning',
            'rejected' => 'danger',
            default => 'pending',
        };
        $poStatusLabel = match($usulan->purchaseOrder?->status) {
            'submitted' => 'Menunggu verifikasi penjual',
            'approved' => 'Disetujui penjual',
            'rejected' => 'Ditolak penjual',
            'cancelled' => 'Dibatalkan',
            null => 'Belum diunggah',
            default => ucfirst($usulan->purchaseOrder->status),
        };
        $poStepTone = match($usulan->purchaseOrder?->status) {
            'approved' => 'complete',
            'submitted' => 'current',
            'rejected', 'cancelled' => 'danger',
            default => 'pending',
        };
        $termStepLabel = $usulan->purchaseOrder?->status === 'approved' ? 'Termin aktif' : 'Menunggu PO disetujui';
        $termStepTone = $usulan->purchaseOrder?->status === 'approved' ? 'current' : 'pending';

        $roleTitle = $isRequester ? 'Anda bertindak sebagai pembeli' : 'Anda bertindak sebagai penjual';
        $roleDescription = $isRequester
            ? 'Tugas Anda: memeriksa penawaran, mengunggah PO, lalu mengirim bukti pembayaran dan bukti potong.'
            : 'Tugas Anda: menanggapi permintaan, menerbitkan penawaran, memverifikasi PO, lalu mengunggah invoice dan faktur.';
        $nextTitle = 'Tidak ada tindakan saat ini';
        $nextDescription = 'Pantau status transaksi dari halaman ini.';
        $nextActionUrl = null;
        $nextActionLabel = null;

        if ($isRequester) {
            if ($usulan->status === 'draft') {
                $nextTitle = 'Kirim permintaan ke perusahaan tujuan';
                $nextDescription = 'Lengkapi data kebutuhan, lalu kirim agar penjual dapat menanggapinya.';
                $nextActionUrl = route('usulan.edit', $usulan);
                $nextActionLabel = 'Lengkapi Permintaan';
            } elseif ($usulan->penawaran_status === 'sent') {
                $nextTitle = 'Periksa dan putuskan penawaran';
                $nextDescription = 'Anda dapat menyetujui, meminta revisi, atau menolak penawaran dari penjual.';
                $nextActionUrl = '#keputusan-penawaran';
                $nextActionLabel = 'Periksa Penawaran';
            } elseif ($usulan->penawaran_status === 'accepted' && ! $usulan->purchaseOrder) {
                $nextTitle = 'Unggah Purchase Order';
                $nextDescription = 'Penawaran sudah disetujui. Lanjutkan dengan mengirim PO kepada penjual.';
                $nextActionUrl = route('purchase-orders.create', ['usulan_id' => $usulan->id]);
                $nextActionLabel = 'Upload PO';
            } elseif ($usulan->purchaseOrder) {
                $nextTitle = $usulan->purchaseOrder->status === 'submitted' ? 'Menunggu verifikasi PO' : 'Lanjutkan ke termin pembayaran';
                $nextDescription = $usulan->purchaseOrder->status === 'submitted'
                    ? 'PO sudah terkirim. Perusahaan penjual perlu memverifikasinya.'
                    : 'Buka PO untuk melihat invoice, pembayaran, dan progres termin.';
                $nextActionUrl = route('purchase-orders.show', $usulan->purchaseOrder);
                $nextActionLabel = 'Buka PO & Termin';
            } else {
                $nextTitle = 'Menunggu penjual';
                $nextDescription = $usulan->penawaran_status === 'revision_requested'
                    ? 'Penjual sedang menyiapkan revisi penawaran.'
                    : 'Penjual perlu menanggapi permintaan dan mengirim penawaran.';
            }
        } elseif ($isSupplier) {
            if (! $usulan->penawaran_id && in_array($usulan->status, ['menunggu', 'ditanggapi', 'disetujui'], true)) {
                $nextTitle = 'Tanggapi dan buat penawaran';
                $nextDescription = 'Konfirmasi permintaan pembeli, kemudian siapkan harga penawaran.';
                $nextActionUrl = '#aksi-transaksi';
                $nextActionLabel = 'Tanggapi Permintaan';
            } elseif (in_array($usulan->penawaran_status, ['draft', 'revision_requested'], true)) {
                $nextTitle = $usulan->penawaran_status === 'revision_requested' ? 'Perbaiki penawaran' : 'Lengkapi penawaran';
                $nextDescription = 'Periksa item dan harga sebelum penawaran dikirim kepada pembeli.';
                $nextActionUrl = route('usulan.quotation.show', $usulan);
                $nextActionLabel = 'Buka Penawaran';
            } elseif ($usulan->purchaseOrder) {
                $nextTitle = $usulan->purchaseOrder->status === 'submitted' ? 'Verifikasi PO masuk' : 'Kelola invoice dan termin';
                $nextDescription = 'Buka PO untuk memverifikasi dokumen dan melanjutkan proses tagihan.';
                $nextActionUrl = route('purchase-orders.show', $usulan->purchaseOrder);
                $nextActionLabel = 'Buka PO Masuk';
            } else {
                $nextTitle = 'Menunggu tindakan pembeli';
                $nextDescription = $usulan->penawaran_status === 'sent'
                    ? 'Penawaran sudah dikirim dan sedang menunggu keputusan pembeli.'
                    : 'Penawaran disetujui. Pembeli perlu mengunggah Purchase Order.';
            }
        }
    @endphp

    <div class="w-full max-w-5xl">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ $usulan->judul }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $usulan->company?->name ?? '-' }} → {{ $usulan->targetCompany?->name ?? 'Perusahaan tujuan belum ditentukan' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('usulan.pdf', $usulan) }}" target="_blank"
                    data-download-loading data-loading-label="Menyiapkan PDF..." data-download-timeout="30000"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="ri-file-pdf-2-line text-red-600"></i>
                    Export Permohonan
                </a>
                <span class="rounded-lg bg-{{ $usulan->status_color }}-100 px-3 py-1 text-sm text-{{ $usulan->status_color }}-700">
                    {{ $usulan->status_label }}
                </span>
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-2 md:grid-cols-4" aria-label="Progres transaksi">
            <div class="rounded-xl border p-3 {{ $stepClasses[$requestStepTone] }}">
                <div class="text-xs font-semibold">1. Permintaan Harga</div>
                <div class="mt-1 text-sm font-medium">{{ $requestStepLabel }}</div>
            </div>
            <div class="rounded-xl border p-3 {{ $stepClasses[$quotationStepTone] }}">
                <div class="text-xs font-semibold">2. Penawaran</div>
                <div class="mt-1 text-sm font-medium">{{ $quotationLabels[$usulan->penawaran_status] ?? $usulan->penawaran_status }}</div>
            </div>
            <div class="rounded-xl border p-3 {{ $stepClasses[$poStepTone] }}">
                <div class="text-xs font-semibold">3. Purchase Order</div>
                <div class="mt-1 text-sm font-medium">{{ $poStatusLabel }}</div>
            </div>
            <div class="rounded-xl border p-3 {{ $stepClasses[$termStepTone] }}">
                <div class="text-xs font-semibold">4. Termin & Invoice</div>
                <div class="mt-1 text-sm font-medium">{{ $termStepLabel }}</div>
            </div>
        </div>

        <div class="mb-4 rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-blue-700">{{ $roleTitle }}</div>
                    <p class="mt-1 text-sm text-slate-600">{{ $roleDescription }}</p>
                    <div class="mt-3 font-semibold text-slate-900">Langkah berikutnya: {{ $nextTitle }}</div>
                    <p class="mt-1 text-sm text-slate-600">{{ $nextDescription }}</p>
                </div>
                @if($nextActionUrl)
                    <a href="{{ $nextActionUrl }}" class="inline-flex shrink-0 justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        {{ $nextActionLabel }}
                    </a>
                @endif
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Pengirim/Pembeli</div>
                <div class="font-medium">{{ $usulan->company?->name ?? '-' }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Penerima/Penjual</div>
                <div class="font-medium">{{ $usulan->targetCompany?->name ?? '-' }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Jenis transaksi</div>
                <div class="font-medium">{{ $usulan->jenis_transaksi === 'campuran' ? 'Barang + Jasa' : ucfirst($usulan->jenis_transaksi) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Tanggal dibutuhkan</div>
                <div class="font-medium">{{ $usulan->tanggal_dibutuhkan?->format('d/m/Y') ?? '-' }}</div>
            </div>
        </div>

        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-2 text-xs font-semibold text-slate-500">Deskripsi kebutuhan</div>
            <div class="text-sm whitespace-pre-wrap">{{ $usulan->deskripsi ?: '-' }}</div>
            @if($usulan->nilai_estimasi > 0)
                <div class="mt-3 text-sm"><span class="text-slate-500">Estimasi anggaran:</span> <strong>Rp {{ number_format($usulan->nilai_estimasi, 0, ',', '.') }}</strong></div>
            @endif
        </div>

        <div id="tanda-tangan-permohonan" class="mb-4 scroll-mt-4 rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-semibold">Tanda Tangan Permohonan Penawaran</h2>
                    <p class="mt-1 text-xs text-slate-500">TTD ini khusus untuk PDF Permohonan Penawaran dan tidak mengubah TTD pada Penawaran Harga.</p>
                </div>
                @if($usulan->signature_path)
                    <span class="w-fit rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">TTD sudah diimpor</span>
                @endif
            </div>

            @if($canEditRequestSignature)
                <form method="POST" action="{{ route('usulan.signature.update', $usulan) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Nama penanda tangan</label>
                            <input name="signature_name" required
                                value="{{ old('signature_name', $usulan->signature_name ?: $usulan->creator?->name) }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Jabatan</label>
                            <input name="signature_position"
                                value="{{ old('signature_position', $usulan->signature_position) }}"
                                placeholder="Contoh: Corporate Account Manager"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Kota</label>
                            <input name="signature_city"
                                value="{{ old('signature_city', $usulan->signature_city ?: 'Yogyakarta') }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Tanggal tanda tangan</label>
                            <input type="date" name="signature_date"
                                value="{{ old('signature_date', optional($usulan->signature_date ?: $usulan->created_at)->format('Y-m-d')) }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    @if($usulan->signature_path)
                        <div>
                            <div class="mb-1 text-xs font-semibold">TTD saat ini</div>
                            <img src="{{ asset('storage/'.$usulan->signature_path) }}" alt="TTD Permohonan"
                                class="h-24 rounded-lg border border-slate-200 bg-slate-50 p-2">
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-xs font-semibold">
                            {{ $usulan->signature_path ? 'Import TTD baru (opsional)' : 'Import / Upload TTD' }}
                        </label>
                        <input type="file" name="signature_file" accept="image/png,image/jpeg,image/webp"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold">
                        <p class="mt-1 text-xs text-slate-500">Gunakan PNG transparan, JPG, atau WebP. Maksimal 2 MB.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Simpan TTD Permohonan</button>
                    </div>
                </form>

                @if($usulan->signature_path)
                    <form method="POST" action="{{ route('usulan.signature.delete', $usulan) }}" class="mt-2"
                        data-confirm-title="Hapus TTD Permohonan?" data-confirm-delete="File TTD akan dihapus, tetapi data nama dan jabatan tetap tersimpan.">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-semibold text-red-600 hover:text-red-700">Hapus file TTD</button>
                    </form>
                @endif
            @else
                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><span class="text-slate-500">Nama:</span> {{ $usulan->signature_name ?: $usulan->creator?->name ?: '-' }}</div>
                    <div><span class="text-slate-500">Jabatan:</span> {{ $usulan->signature_position ?: '-' }}</div>
                </div>
                @if($usulan->signature_path)
                    <img src="{{ asset('storage/'.$usulan->signature_path) }}" alt="TTD Permohonan"
                        class="mt-3 h-24 rounded-lg border border-slate-200 bg-slate-50 p-2">
                @else
                    <p class="mt-3 text-xs text-slate-500">Pemilik permohonan belum mengimpor file TTD khusus.</p>
                @endif
            @endif
        </div>

        @if ($usulan->items->count())
            <div class="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-5 py-3 text-sm font-semibold">Item yang Diminta</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left">No</th>
                                <th class="px-4 py-2 text-left">Item</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                                <th class="px-4 py-2 text-left">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($usulan->items as $i => $item)
                                @php
                                    $itemPoints = preg_split('/\R/', (string) $item->catatan, -1, PREG_SPLIT_NO_EMPTY);
                                @endphp
                                <tr>
                                    <td class="px-4 py-2">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2">
                                        <div class="font-medium">{{ $item->judul }}</div>
                                        @if ($itemPoints)
                                            <ol class="mt-1 list-[lower-alpha] space-y-0.5 pl-5 text-xs text-slate-600">
                                                @foreach ($itemPoints as $point)
                                                    <li>{{ trim($point) }}</li>
                                                @endforeach
                                            </ol>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                    <td class="px-4 py-2">{{ $item->satuan ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($usulan->attachments->count())
            <div class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-2 text-sm font-semibold">Lampiran Permintaan</div>
                <div class="space-y-2">
                    @foreach ($usulan->attachments as $att)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <div><span class="mr-2 rounded bg-slate-200 px-2 py-0.5 text-xs">{{ $att->tipe }}</span><span class="text-sm">{{ $att->nama_file }}</span></div>
                            <a href="{{ route('usulan.attachments.download', [$usulan, $att]) }}" class="text-sm font-semibold text-blue-600">Download</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($usulan->tanggapan)
            <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="mb-2 text-xs font-semibold text-blue-700">Tanggapan penerima</div>
                <div class="text-sm whitespace-pre-wrap">{{ $usulan->tanggapan }}</div>
            </div>
        @endif

        @if ($usulan->penawaran_id)
            <div id="penawaran-paperless" class="mb-4 scroll-mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-xs font-semibold text-emerald-700">Penawaran Paperless</div>
                        <div class="mt-1 font-medium">{{ $usulan->penawaran?->docNumber?->doc_no ?? 'Draft Penawaran' }}</div>
                        <span class="mt-2 inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $quotationClasses[$usulan->penawaran_status] ?? $quotationClasses['none'] }}">
                            {{ $quotationLabels[$usulan->penawaran_status] ?? $usulan->penawaran_status }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($canViewLinkedPenawaran)
                            <a href="{{ route('usulan.quotation.show', $usulan) }}" class="rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700">Lihat Penawaran Harga</a>
                            <a href="{{ route('usulan.quotation.pdf', $usulan) }}" target="_blank"
                                data-download-loading data-loading-label="Menyiapkan PDF..." data-download-timeout="30000"
                                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Export PDF Khusus</a>
                        @endif
                        @if($canEditLinkedQuotation)
                            <a href="{{ route('usulan.quotation.show', $usulan) }}#tanda-tangan-penawaran"
                                class="rounded-xl border border-violet-300 bg-white px-4 py-2.5 text-sm font-semibold text-violet-700">Import TTD Penawaran</a>
                        @endif
                        @if($canRespond && in_array($usulan->penawaran_status, ['draft', 'revision_requested'], true))
                            <form method="POST" action="{{ route('usulan.kirim-penawaran', $usulan) }}">
                                @csrf
                                <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">
                                    {{ $usulan->penawaran_status === 'revision_requested' ? 'Kirim Ulang Penawaran' : 'Kirim ke Pembeli' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($usulan->penawaran_tanggapan)
                    <div class="mt-3 rounded-lg bg-white/70 p-3 text-sm"><strong>Catatan pembeli:</strong> {{ $usulan->penawaran_tanggapan }}</div>
                @endif
            </div>
        @endif

        @if($isRequester && $usulan->penawaran_status === 'sent')
            <form id="keputusan-penawaran" method="POST" action="{{ route('usulan.tanggapi-penawaran', $usulan) }}" class="mb-4 scroll-mt-4 rounded-xl border border-blue-200 bg-white p-5">
                @csrf
                <h2 class="font-semibold">Keputusan Penawaran</h2>
                <p class="mt-1 text-sm text-slate-500">Periksa dokumen penawaran sebelum menyetujui atau meminta revisi.</p>
                <textarea name="penawaran_tanggapan" rows="3" class="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Catatan revisi atau alasan penolakan (wajib untuk revisi/penolakan)"></textarea>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button name="action" value="accepted" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Setujui Penawaran</button>
                    <button name="action" value="revision_requested" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white">Minta Revisi</button>
                    <button name="action" value="rejected" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white">Tolak</button>
                </div>
            </form>
        @endif

        @if($usulan->purchaseOrder)
            <div class="mb-4 flex items-center justify-between rounded-xl border border-violet-200 bg-violet-50 p-5">
                <div>
                    <div class="text-xs font-semibold text-violet-700">Purchase Order Terhubung</div>
                    <div class="mt-1 font-medium">{{ $usulan->purchaseOrder->nomor_po }}</div>
                </div>
                <a href="{{ route('purchase-orders.show', $usulan->purchaseOrder) }}" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white">Lihat PO & Termin</a>
            </div>
        @endif

        <div id="aksi-transaksi" class="mb-4 flex scroll-mt-4 flex-wrap gap-2">
            <a href="{{ route('usulan.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold">Kembali</a>

            @if ($isRequester && in_array($usulan->status, ['draft', 'menunggu'], true) && !$usulan->penawaran_id)
                <a href="{{ route('usulan.edit', $usulan) }}" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white">Edit Permintaan</a>
            @endif

            @if ($canRespond && in_array($usulan->status, ['menunggu', 'ditanggapi'], true) && !$usulan->penawaran_id)
                <button type="button" onclick="document.getElementById('tanggapanModal').classList.remove('hidden')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Tanggapi & Buat Penawaran</button>
            @endif

            @if ($canRespond && $usulan->status === 'disetujui' && !$usulan->penawaran_id)
                <form action="{{ route('usulan.buat-penawaran', $usulan) }}" method="POST">
                    @csrf
                    <input type="hidden" name="copy_items" value="1">
                    <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Buat Penawaran</button>
                </form>
            @endif

            @if ($isRequester && $usulan->penawaran_status === 'accepted' && !$usulan->purchaseOrder)
                <a href="{{ route('purchase-orders.create', ['usulan_id' => $usulan->id]) }}" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white">Upload Purchase Order</a>
            @endif

            @if ($isRequester && $usulan->status === 'draft' && !$usulan->penawaran_id)
                <form action="{{ route('usulan.destroy', $usulan) }}" method="POST" data-confirm-title="Hapus Permintaan?" data-confirm-delete="Permintaan harga ini akan dihapus permanen.">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700">Hapus</button>
                </form>
            @endif
        </div>
    </div>

    @if($canRespond)
        <div id="tanggapanModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="w-full max-w-lg rounded-xl bg-white p-6" onclick="event.stopPropagation()">
                <h2 class="mb-4 text-lg font-semibold">Tanggapi Permintaan Harga</h2>
                <form method="POST" action="{{ route('usulan.tanggapi', $usulan) }}">
                    @csrf
                    <label class="mb-1 block text-xs font-semibold">Tanggapan</label>
                    <textarea name="tanggapan" rows="4" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    <input type="hidden" name="status" value="disetujui">
                    <label class="mb-1 mt-4 block text-xs font-semibold">Pembuatan penawaran</label>
                    <select name="penawaran_action" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="from_usulan" {{ $usulan->items->isEmpty() ? 'disabled' : '' }}>Buat penawaran dari item permintaan</option>
                        <option value="empty">Buat penawaran kosong</option>
                        <option value="none">Terima tanpa membuat penawaran sekarang</option>
                    </select>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="this.closest('#tanggapanModal').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2">Batal</button>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
