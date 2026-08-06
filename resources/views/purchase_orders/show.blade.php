@extends('layouts.app', ['title' => 'Detail Purchase Order'])

@section('content')
    @php
        // Seluruh pencatatan termin -- invoice, faktur, pembayaran, dan PPh -- dilakukan
        // penjual. Pembeli hanya melihat. PO lama tanpa perusahaan penjual tetap
        // dikerjakan pembeli karena tidak ada lawan transaksi di sistem.
        $canBilling = ($isSeller || $isLegacy) && auth()->user()->hasPermission('create-purchase-order');
        $canPayment = $canBilling;
        $progress = (float) $po->total > 0 ? min(100, round(($po->total_pelunasan / (float) $po->total) * 100, 1)) : 0;
        $statusLabels = [
            'draft' => 'Draft', 'invoiced' => 'Ditagihkan', 'partially_paid' => 'Dibayar Sebagian',
            'paid' => 'Lunas', 'overdue' => 'Terlambat',
        ];
        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700', 'invoiced' => 'bg-blue-100 text-blue-700',
            'partially_paid' => 'bg-amber-100 text-amber-800', 'paid' => 'bg-emerald-100 text-emerald-700',
            'overdue' => 'bg-red-100 text-red-700',
        ];
        $pphOptions = [
            'none' => 'Tidak ada', 'pph_21' => 'PPh 21', 'pph_22' => 'PPh 22',
            'pph_23' => 'PPh 23', 'pph_4_2' => 'PPh Final 4(2)', 'other' => 'PPh lainnya',
        ];
        $unbilledTerm = $po->terms->first(fn ($term) => blank($term->nomor_invoice) && blank($term->invoice_path));
        $payableTerm = $po->terms->first(fn ($term) =>
            (filled($term->nomor_invoice) || filled($term->invoice_path))
            && $term->calculateStatus() !== 'paid'
        );
        $allTermsPaid = $po->terms->isNotEmpty() && $po->terms->every(fn ($term) => $term->calculateStatus() === 'paid');
        $roleTitle = $isBuyer ? 'Anda bertindak sebagai pembeli' : ($isSeller ? 'Anda bertindak sebagai penjual' : 'PO lama');
        $roleDescription = $isBuyer
            ? 'Anda mengunggah PO dan memantau tagihan serta pembayarannya.'
            : 'Anda memverifikasi PO, menerbitkan invoice/faktur, dan mencatat pembayaran serta PPh.';
        $nextTitle = 'Pantau status Purchase Order';
        $nextDescription = 'Belum ada tindakan yang perlu dilakukan saat ini.';
        $nextActionUrl = null;
        $nextActionLabel = null;

        if ($isSeller && in_array($po->status, ['submitted', 'rejected'], true)) {
            $nextTitle = 'Verifikasi PO masuk';
            $nextDescription = 'Periksa dokumen dan nilai PO. Saat disetujui, sistem langsung membuat termin.';
            $nextActionUrl = '#verifikasi-po';
            $nextActionLabel = 'Verifikasi Sekarang';
        } elseif ($isBuyer && $po->status === 'submitted') {
            $nextTitle = 'Menunggu penjual memverifikasi PO';
            $nextDescription = 'PO sudah terkirim. Termin akan muncul setelah PO disetujui penjual.';
        } elseif ($po->status === 'approved' && $isSeller) {
            if ($unbilledTerm) {
                $nextTitle = 'Terbitkan invoice termin berikutnya';
                $nextDescription = 'Lengkapi invoice dan faktur sebelum mencatat pembayarannya.';
                $nextActionUrl = '#term-'.$unbilledTerm->id;
                $nextActionLabel = 'Isi Invoice';
            } elseif ($allTermsPaid) {
                $nextTitle = 'Seluruh termin sudah lunas';
                $nextDescription = 'Semua pembayaran sudah tercatat.';
            } elseif ($payableTerm) {
                $nextTitle = 'Catat pembayaran termin';
                $nextDescription = 'Invoice sudah terbit. Isi tanggal bayar, nilai transfer, dan bukti potong bila ada PPh.';
                $nextActionUrl = '#term-'.$payableTerm->id;
                $nextActionLabel = 'Catat Pembayaran';
            } else {
                $nextTitle = 'Pantau status termin';
                $nextDescription = 'Belum ada termin yang perlu ditindaklanjuti.';
            }
        } elseif ($po->status === 'approved' && $isBuyer) {
            if ($allTermsPaid) {
                $nextTitle = 'Seluruh termin sudah lunas';
                $nextDescription = 'Semua pembayaran sudah dicatat penjual.';
            } elseif ($unbilledTerm) {
                $nextTitle = 'Menunggu invoice dari penjual';
                $nextDescription = 'Penjual belum menerbitkan invoice untuk termin berikutnya.';
            } else {
                $nextTitle = 'Menunggu pencatatan pembayaran';
                $nextDescription = 'Invoice sudah terbit. Penjual akan mencatat pembayaran dan PPh-nya.';
            }
        }
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold">{{ $po->nomor_po ?? 'Purchase Order' }}</h1>
                @if($isBuyer)
                    <span class="rounded bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-700">PO Keluar</span>
                @elseif($isSeller)
                    <span class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">PO Masuk</span>
                @endif
            </div>
            <div class="mt-1 text-sm text-slate-500">
                {{ $po->isExternalCustomerOrder() ? ($po->pembeli_nama ?: 'Pelanggan luar') : ($po->company?->name ?? '-') }}
                → {{ $po->isExternalCustomerOrder() ? ($po->company?->name ?? '-') : ($po->supplierCompany?->name ?? $po->supplier_nama) }}
            </div>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold">Kembali</a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold">Data belum dapat disimpan:</div>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($po->usulan)
        <div class="mb-4 flex flex-col gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs font-semibold text-violet-700">Alur transaksi terhubung</div>
                <div class="mt-1 text-sm">Permintaan Harga → Penawaran → <strong>Purchase Order</strong> → Termin & Invoice</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('penawaran-harga.show', $po->usulan) }}" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-violet-700">Permintaan Harga</a>
                @if($po->penawaran)
                    <a href="{{ route('penawaran-harga.quotation.show', $po->usulan) }}" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-violet-700">Penawaran Harga</a>
                @endif
            </div>
        </div>
    @endif

    @if(!$isLegacy)
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
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><div class="text-xs text-slate-500">Judul</div><div class="font-semibold">{{ $po->judul }}</div></div>
                    <div><div class="text-xs text-slate-500">Tanggal PO</div><div class="font-semibold">{{ $po->tgl_po?->format('d M Y') }}</div></div>
                    @if($po->isExternalCustomerOrder())
                        <div><div class="text-xs text-slate-500">Pembeli (pelanggan luar)</div><div class="font-semibold">{{ $po->pembeli_nama ?: '-' }}</div>@if($po->pembeli_alamat)<div class="mt-0.5 text-xs text-slate-500">{{ $po->pembeli_alamat }}</div>@endif</div>
                        <div><div class="text-xs text-slate-500">Penjual</div><div class="font-semibold">{{ $po->company?->name ?? '-' }}</div></div>
                    @else
                        <div><div class="text-xs text-slate-500">Pembeli</div><div class="font-semibold">{{ $po->company?->name ?? '-' }}</div></div>
                        <div><div class="text-xs text-slate-500">Penjual</div><div class="font-semibold">{{ $po->supplierCompany?->name ?? $po->supplier_nama }}</div></div>
                    @endif
                    <div><div class="text-xs text-slate-500">Jenis transaksi</div><div class="font-semibold">{{ $po->jenis_transaksi === 'campuran' ? 'Barang + Jasa' : ucfirst($po->jenis_transaksi) }}</div></div>
                    <div>
                        <div class="text-xs text-slate-500">Dokumen PO</div>
                        @if($po->po_file_path)<a href="{{ route('purchase-orders.document.download', $po) }}" class="font-semibold text-blue-600">Unduh dokumen</a>@else<span class="text-slate-400">Belum diunggah</span>@endif
                    </div>
                </div>
                @if($quotationTotal !== null)
                    <div class="mt-4 rounded-xl {{ abs($poDifference) >= 0.01 ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }} p-3 text-sm">
                        <div class="flex justify-between gap-4"><span>Nilai penawaran</span><strong>Rp {{ number_format($quotationTotal, 0, ',', '.') }}</strong></div>
                        <div class="mt-1 flex justify-between gap-4"><span>Selisih PO</span><strong>Rp {{ number_format($poDifference, 0, ',', '.') }}</strong></div>
                        @if(abs($poDifference) >= 0.01)<p class="mt-2 text-xs">Nilai berbeda. Penjual wajib memberikan catatan saat menyetujui PO.</p>@endif
                    </div>
                @endif
            </div>

            @if($isSeller && in_array($po->status, ['submitted', 'rejected'], true))
                <form id="verifikasi-po" method="POST" action="{{ route('purchase-orders.verify', $po) }}" class="scroll-mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    @csrf
                    <h2 class="font-semibold">Verifikasi PO Masuk</h2>
                    <p class="mt-1 text-sm text-slate-600">Jika disetujui, sistem otomatis membuat termin pembayaran. Jumlah default dapat diubah.</p>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Jumlah termin</label>
                            <input type="number" name="default_term_count" value="5" min="1" max="24" class="w-full rounded-xl border border-blue-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Jatuh tempo termin pertama</label>
                            <input type="date" name="first_due_date" value="{{ now()->addMonth()->format('Y-m-d') }}" class="w-full rounded-xl border border-blue-200 px-3 py-2 text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="mb-1 block text-xs font-semibold">Catatan verifikasi</label>
                            <textarea name="verification_notes" rows="2" class="w-full rounded-xl border border-blue-200 px-3 py-2 text-sm" placeholder="Wajib jika PO ditolak"></textarea>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <button name="decision" value="approved" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Setujui & Buat Termin</button>
                        <button name="decision" value="rejected" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white">Tolak PO</button>
                    </div>
                </form>
            @elseif($po->verification_notes)
                <div class="rounded-xl border {{ $po->status === 'rejected' ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' }} p-4 text-sm">
                    <strong>Catatan verifikasi:</strong> {{ $po->verification_notes }}
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="text-xs text-slate-500">Status PO</div>
            <div class="mt-1 text-lg font-semibold">{{ match($po->status) {'submitted' => 'Menunggu Verifikasi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => ucfirst($po->status)} }}</div>
            <div class="mt-4 text-xs text-slate-500">Total PO</div>
            <div class="text-2xl font-semibold">Rp {{ number_format((float) $po->total, 0, ',', '.') }}</div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-emerald-500" style="width: {{ $progress }}%"></div></div>
            <div class="mt-2 text-xs text-slate-500">Pelunasan tercatat {{ $progress }}%</div>
            <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Terjadwal</dt><dd class="font-semibold">Rp {{ number_format($po->total_terjadwal, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Belum terjadwal</dt><dd class="font-semibold">Rp {{ number_format($po->sisa_belum_terjadwal, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Sisa pembayaran</dt><dd class="font-semibold text-red-600">Rp {{ number_format($po->sisa_pembayaran, 0, ',', '.') }}</dd></div>
            </dl>
        </div>
    </div>

    @if($po->status === 'approved' || $isLegacy)
        <div class="mt-6">
            <h2 class="text-lg font-semibold">Jadwal Termin Pembayaran</h2>
            <p class="mt-1 text-sm text-slate-500">Penjual menerbitkan invoice/faktur serta mencatat pembayaran dan bukti potong. Pembeli memantau.</p>
        </div>

        {{-- Form selalu tampil untuk penjual, tidak lagi menunggu adanya sisa belum
             terjadwal. Batas "total termin tidak boleh melebihi nilai PO" tetap dijaga
             di server, jadi yang menolak adalah penyimpanan, bukan form yang menghilang. --}}
        @if($canBilling)
            @php $sisaBelumTerjadwal = (int) round($po->sisa_belum_terjadwal); @endphp
            <form method="POST" action="{{ route('purchase-orders.terms.store', $po) }}" class="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 sm:grid-cols-3 sm:items-end">
                @csrf
                <div><label class="mb-1 block text-xs font-semibold">Tanggal jatuh tempo</label><input type="date" name="tanggal_jatuh_tempo" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold">Nilai tagihan</label><input type="number" name="nilai_tagihan" min="1" step="1" value="{{ $sisaBelumTerjadwal > 0 ? $sisaBelumTerjadwal : '' }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">+ Tambah Termin</button>
                @if($sisaBelumTerjadwal <= 0)
                    <p class="text-xs text-amber-700 sm:col-span-3">Seluruh nilai PO sudah terjadwal. Kecilkan dulu nilai salah satu termin agar ada sisa yang bisa dijadwalkan ke termin baru.</p>
                @endif
            </form>
        @endif

        @php
            // Mengikuti jumlah termin saat ini: menyusut kalau pelunasan lebih cepat,
            // bertambah kalau jadwalnya diperpanjang.
            $jumlahTermin = $po->jumlahTerminLabel();
        @endphp

        <div class="mt-4 space-y-4">
            @forelse($po->terms as $term)
                @php
                    $displayStatus = $term->calculateStatus();
                    $hasActivity = (float) $term->nilai_dibayar > 0 || (float) $term->nilai_pph > 0 || collect([$term->invoice_path, $term->faktur_path, $term->bukti_bayar_path, $term->bukti_potong_pph_path])->filter()->isNotEmpty();
                @endphp
                <div id="term-{{ $term->id }}" class="scroll-mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><div class="font-semibold">Pembayaran ke-{{ $term->pembayaran_ke }} dari {{ $jumlahTermin }}</div><div class="text-sm text-slate-500">{{ $term->tanggal_jatuh_tempo?->format('d M Y') }}</div></div>
                        <div class="flex items-center gap-2"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$displayStatus] ?? $statusClasses['draft'] }}">{{ $statusLabels[$displayStatus] ?? $displayStatus }}</span><strong>Rp {{ number_format((float) $term->nilai_tagihan, 0, ',', '.') }}</strong></div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                        <form method="POST" action="{{ route('purchase-orders.terms.billing.update', [$po, $term]) }}" enctype="multipart/form-data" class="space-y-3 rounded-xl border p-4 {{ $canBilling ? 'border-blue-300 bg-blue-50/40 ring-2 ring-blue-100' : 'border-slate-200 bg-slate-50/60' }}">
                            @csrf @method('PUT')
                            <div class="flex items-center justify-between"><h3 class="font-semibold">Invoice & Faktur</h3><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $canBilling ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">{{ $canBilling ? 'Tugas Anda' : 'Oleh penjual' }}</span></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="mb-1 block text-xs text-slate-500">Jatuh tempo</label><input type="date" name="tanggal_jatuh_tempo" value="{{ $term->tanggal_jatuh_tempo?->format('Y-m-d') }}" required {{ !$canBilling ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-xs text-slate-500">Nilai tagihan</label><input type="number" name="nilai_tagihan" value="{{ (int) round($term->nilai_tagihan) }}" min="1" step="1" required {{ !$canBilling ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                            </div>
                            <div><label class="mb-1 block text-xs text-slate-500">Nomor invoice</label><input name="nomor_invoice" value="{{ $term->nomor_invoice }}" {{ !$canBilling ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                            <div><label class="mb-1 block text-xs text-slate-500">Tanggal invoice</label><input type="date" name="tanggal_invoice" value="{{ $term->tanggal_invoice?->format('Y-m-d') }}" {{ !$canBilling ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                            <div><label class="mb-1 block text-xs text-slate-500">File invoice</label>@if($canBilling)<input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">@endif @if($term->invoice_path)<a href="{{ route('purchase-orders.terms.documents.download', [$po, $term, 'invoice']) }}" class="mt-1 inline-block text-xs font-semibold text-blue-600">Unduh invoice</a>@endif</div>
                            <div><label class="mb-1 block text-xs text-slate-500">Nomor faktur pajak</label><input name="nomor_faktur" value="{{ $term->nomor_faktur }}" {{ !$canBilling ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                            <div><label class="mb-1 block text-xs text-slate-500">File faktur</label>@if($canBilling)<input type="file" name="faktur_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">@endif @if($term->faktur_path)<a href="{{ route('purchase-orders.terms.documents.download', [$po, $term, 'faktur']) }}" class="mt-1 inline-block text-xs font-semibold text-blue-600">Unduh faktur</a>@endif</div>
                            <div><label class="mb-1 block text-xs text-slate-500">Catatan</label><textarea name="catatan" rows="2" {{ !$canBilling ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ $term->catatan }}</textarea></div>
                            @if($canBilling)<button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan & Kirim Tagihan</button>@endif
                        </form>

                        <div class="space-y-3 rounded-xl border p-4 {{ $canPayment ? 'border-violet-300 bg-violet-50/40 ring-2 ring-violet-100' : 'border-slate-200 bg-slate-50/60' }}">
                            <form method="POST" action="{{ route('purchase-orders.terms.payment.update', [$po, $term]) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf @method('PUT')
                                <div class="flex items-center justify-between"><h3 class="font-semibold">Pembayaran & PPh</h3><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $canPayment ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-500' }}">{{ $canPayment ? 'Tugas Anda' : 'Oleh penjual' }}</span></div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1 block text-xs text-slate-500">Tanggal bayar</label><input type="date" name="tanggal_bayar" value="{{ $term->tanggal_bayar?->format('Y-m-d') }}" {{ !$canPayment ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                                    <div><label class="mb-1 block text-xs text-slate-500">Nilai transfer</label><input type="number" name="nilai_dibayar" value="{{ (int) round($term->nilai_dibayar) }}" min="0" step="1" {{ !$canPayment ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                                </div>
                                <div><label class="mb-1 block text-xs text-slate-500">Bukti bayar</label>@if($canPayment)<input type="file" name="bukti_bayar_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">@endif @if($term->bukti_bayar_path)<a href="{{ route('purchase-orders.terms.documents.download', [$po, $term, 'bukti-bayar']) }}" class="mt-1 inline-block text-xs font-semibold text-blue-600">Unduh bukti bayar</a>@endif</div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1 block text-xs text-slate-500">Jenis PPh</label><select name="jenis_pph" {{ !$canPayment ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">@foreach($pphOptions as $value => $label)<option value="{{ $value }}" {{ ($term->jenis_pph ?? 'none') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                                    <div><label class="mb-1 block text-xs text-slate-500">Nilai PPh</label><input type="number" name="nilai_pph" value="{{ (int) round($term->nilai_pph) }}" min="0" step="1" {{ !$canPayment ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
                                </div>
                                <div><label class="mb-1 block text-xs text-slate-500">Bukti potong PPh</label>@if($canPayment)<input type="file" name="bukti_potong_pph_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">@endif @if($term->bukti_potong_pph_path)<a href="{{ route('purchase-orders.terms.documents.download', [$po, $term, 'bukti-potong-pph']) }}" class="mt-1 inline-block text-xs font-semibold text-blue-600">Unduh bukti potong</a>@endif</div>
                                <div class="rounded-lg bg-slate-50 p-3 text-xs"><div class="flex justify-between"><span>Transfer + PPh</span><strong>Rp {{ number_format($term->nilai_pelunasan, 0, ',', '.') }}</strong></div><div class="mt-1 flex justify-between"><span>Sisa termin</span><strong class="text-red-600">Rp {{ number_format($term->sisa_tagihan, 0, ',', '.') }}</strong></div></div>
                                @if($canPayment && (filled($term->nomor_invoice) || filled($term->invoice_path)))
                                    <button class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white sm:w-auto">Simpan Pembayaran</button>
                                @elseif($canPayment)
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Terbitkan invoice termin ini terlebih dahulu. Form pembayaran akan aktif setelah invoice tersedia.</div>
                                @endif
                            </form>
                        </div>
                    </div>

                    @if($canBilling && !$hasActivity)
                        <form method="POST" action="{{ route('purchase-orders.terms.destroy', [$po, $term]) }}" class="border-t border-slate-100 px-5 py-3 text-right" data-confirm-title="Hapus Termin?" data-confirm-delete="Termin ke-{{ $term->pembayaran_ke }} akan dihapus dan termin sesudahnya dinaikkan nomornya.">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600">Hapus termin ke-{{ $term->pembayaran_ke }}</button></form>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Belum ada termin pembayaran.</div>
            @endforelse
        </div>
    @else
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">Jadwal termin akan aktif setelah perusahaan penjual menyetujui PO.</div>
    @endif
@endsection
