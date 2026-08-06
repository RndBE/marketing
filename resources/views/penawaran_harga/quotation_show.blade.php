@extends('layouts.app', ['title' => 'Penawaran Harga dari Permohonan'])

@section('content')
    @php
        $penawaran = $usulan->penawaran;
        $signature = $penawaran->signatures->first();
        $statusLabels = [
            'draft' => 'Draft',
            'sent' => 'Sudah dikirim ke pembeli',
            'accepted' => 'Disetujui pembeli',
            'revision_requested' => 'Revisi diminta',
            'rejected' => 'Ditolak pembeli',
        ];
        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'sent' => 'bg-blue-100 text-blue-700',
            'accepted' => 'bg-emerald-100 text-emerald-700',
            'revision_requested' => 'bg-amber-100 text-amber-800',
            'rejected' => 'bg-red-100 text-red-700',
        ];
        $subtotal = $penawaran->calcItemsSubtotal();
        $discount = $penawaran->calcDiscountAmount();
        $tax = $penawaran->calcTaxAmount();
        $grandTotal = $penawaran->calcGrandTotal();
    @endphp

    <div class="w-full max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-violet-700">Penawaran Harga dari Permohonan</div>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $penawaran->judul }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $usulan->targetCompany?->name ?? '-' }} → {{ $usulan->company?->name ?? '-' }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ $quotationNumber }}</span>
                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $statusClasses[$usulan->penawaran_status] ?? $statusClasses['draft'] }}">
                        {{ $statusLabels[$usulan->penawaran_status] ?? $usulan->penawaran_status }}
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('penawaran-harga.show', $usulan) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke Permohonan</a>
                <a href="{{ route('penawaran-harga.quotation.pdf', $usulan) }}" target="_blank"
                    data-download-loading data-loading-label="Menyiapkan PDF..." data-download-timeout="30000"
                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Export PDF Khusus</a>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            <strong>Dokumen khusus alur Permohonan Harga.</strong>
            Halaman ini terpisah dari Detail Penawaran dan Daftar Penawaran biasa.
            @if($canEditQuotation)
                Lengkapi harga, keterangan, dan TTD lalu simpan sebelum mengirim kepada pembeli.
            @elseif($isRequester)
                Anda melihat dokumen yang diterbitkan oleh perusahaan penjual.
            @endif
        </div>

        <form id="quotation-form" method="POST" action="{{ route('penawaran-harga.quotation.update', $usulan) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-slate-900">Identitas Surat</h2>
                    @if(!$canEditQuotation)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Mode lihat</span>
                    @endif
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Kepada</label>
                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm font-medium">{{ $usulan->company?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">No. Surat Permohonan</label>
                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm font-medium">{{ $requestDocumentNumber }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Penawaran</label>
                        <input type="date" name="tanggal_penawaran"
                            value="{{ old('tanggal_penawaran', optional($penawaran->tanggal_penawaran ?: $penawaran->created_at)->format('Y-m-d')) }}"
                            @disabled(!$canEditQuotation)
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50 disabled:text-slate-600">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Pekerjaan</label>
                        <input name="nama_pekerjaan" value="{{ old('nama_pekerjaan', $penawaran->nama_pekerjaan ?: $penawaran->judul) }}"
                            @disabled(!$canEditQuotation)
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50 disabled:text-slate-600">
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Item dan Harga</h2>
                    <p class="mt-1 text-xs text-slate-500">Struktur tabel ini sama dengan dokumen Penawaran Harga yang diekspor.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="w-14 px-3 py-3 text-center">No.</th>
                                <th class="min-w-[360px] px-3 py-3 text-left">Item / spesifikasi / pekerjaan</th>
                                <th class="w-28 px-3 py-3 text-center">Product No.</th>
                                <th class="w-32 px-3 py-3 text-left">Volume</th>
                                <th class="w-44 px-3 py-3 text-right">Harga Satuan</th>
                                <th class="w-44 px-3 py-3 text-right">Harga Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($penawaran->items as $index => $item)
                                @php
                                    $unitPrice = $item->calcUnitSubtotal();
                                    $itemTotal = $item->calcSubtotal();
                                @endphp
                                <tr data-quotation-item-row>
                                    <td class="px-3 py-4 text-center align-top font-semibold">{{ $index + 1 }}</td>
                                    <td class="px-3 py-4 align-top">
                                        <input name="items[{{ $item->id }}][judul]" value="{{ old('items.'.$item->id.'.judul', $item->judul) }}"
                                            @disabled(!$canEditQuotation)
                                            class="w-full rounded-lg border border-slate-200 px-3 py-2 font-semibold disabled:bg-slate-50">
                                        <textarea name="items[{{ $item->id }}][catatan]" rows="3"
                                            @disabled(!$canEditQuotation)
                                            placeholder="Detail per poin, satu baris untuk setiap poin"
                                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs disabled:bg-slate-50">{{ old('items.'.$item->id.'.catatan', $item->catatan) }}</textarea>
                                        @if($item->details->count() > 1)
                                            <div class="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-slate-600">
                                                @foreach($item->details as $detail)
                                                    <div>{{ chr(96 + min($loop->iteration, 26)) }}. {{ $detail->nama }}{{ $detail->spesifikasi ? ' - '.$detail->spesifikasi : '' }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-center align-top text-xs text-slate-600">{{ $item->product?->kode ?: '-' }}</td>
                                    <td class="px-3 py-4 align-top">
                                        <div class="grid grid-cols-2 gap-1">
                                            <input type="number" step="0.01" min="0.01" name="items[{{ $item->id }}][qty]"
                                                value="{{ old('items.'.$item->id.'.qty', (float) $item->qty) }}"
                                                data-quotation-qty @disabled(!$canEditQuotation)
                                                class="w-full rounded-lg border border-slate-200 px-2 py-2 text-right disabled:bg-slate-50">
                                            <input name="items[{{ $item->id }}][satuan]"
                                                value="{{ old('items.'.$item->id.'.satuan', $item->satuan) }}"
                                                @disabled(!$canEditQuotation)
                                                class="w-full rounded-lg border border-slate-200 px-2 py-2 disabled:bg-slate-50">
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 align-top">
                                        <div class="flex items-center rounded-lg border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-100">
                                            <span class="px-2 text-xs text-slate-500">Rp</span>
                                            <input type="number" min="0" name="items[{{ $item->id }}][unit_price]"
                                                value="{{ old('items.'.$item->id.'.unit_price', $unitPrice) }}"
                                                data-quotation-unit-price @disabled(!$canEditQuotation)
                                                class="min-w-0 flex-1 rounded-r-lg border-0 px-2 py-2 text-right focus:ring-0 disabled:bg-slate-50">
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right align-top font-semibold" data-quotation-row-total>
                                        Rp {{ number_format($itemTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-slate-300 bg-slate-50 text-sm">
                            <tr>
                                <td colspan="5" class="px-3 py-2 text-right font-semibold">Jumlah</td>
                                <td class="px-3 py-2 text-right font-semibold" data-quotation-subtotal>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-3 py-2 text-right font-bold">Total Harga Saat Ini</td>
                                <td class="px-3 py-2 text-right font-bold text-emerald-700">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">Diskon, Pajak, dan Masa Berlaku</h2>
                    <div class="mt-4 space-y-4">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="hidden" name="discount_enabled" value="0">
                                <input type="checkbox" name="discount_enabled" value="1" @checked($penawaran->discount_enabled)
                                    @disabled(!$canEditQuotation) class="rounded border-slate-300">
                                Gunakan diskon
                            </label>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <select name="discount_type" @disabled(!$canEditQuotation)
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50">
                                    <option value="percent" @selected($penawaran->discount_type === 'percent')>Persen (%)</option>
                                    <option value="fixed" @selected($penawaran->discount_type === 'fixed')>Nominal (Rp)</option>
                                </select>
                                <input type="number" min="0" step="0.01" name="discount_value" value="{{ old('discount_value', $penawaran->discount_value ?? 0) }}"
                                    @disabled(!$canEditQuotation)
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-right text-sm disabled:bg-slate-50">
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="hidden" name="tax_enabled" value="0">
                                <input type="checkbox" name="tax_enabled" value="1" @checked($penawaran->tax_enabled)
                                    @disabled(!$canEditQuotation) class="rounded border-slate-300">
                                Gunakan PPN
                            </label>
                            <div class="mt-3">
                                <label class="mb-1 block text-xs text-slate-500">Tarif PPN (%)</label>
                                <input type="number" min="0" max="100" step="0.01" name="tax_rate" value="{{ old('tax_rate', $penawaran->tax_rate ?? 11) }}"
                                    @disabled(!$canEditQuotation)
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-right text-sm disabled:bg-slate-50">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Penawaran berlaku sampai</label>
                            <input type="date" name="valid_until" value="{{ old('valid_until', $penawaran->validity?->sampai ? \Carbon\Carbon::parse($penawaran->validity->sampai)->format('Y-m-d') : '') }}"
                                @disabled(!$canEditQuotation)
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">Keterangan Penawaran</h2>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Catatan umum</label>
                            <textarea name="catatan" rows="3" @disabled(!$canEditQuotation)
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50">{{ old('catatan', $penawaran->catatan) }}</textarea>
                        </div>
                        @foreach($penawaran->terms as $term)
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ $term->judul ?: 'Keterangan '.$loop->iteration }}</label>
                                <textarea name="terms[{{ $term->id }}]" rows="2" @disabled(!$canEditQuotation)
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50">{{ old('terms.'.$term->id, $term->isi) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <section id="tanda-tangan-penawaran" class="scroll-mt-4 rounded-2xl border border-violet-200 bg-violet-50/50 p-5">
                <div class="mb-4">
                    <h2 class="font-semibold text-slate-900">Tanda Tangan Penawaran Harga</h2>
                    <p class="mt-1 text-xs text-slate-500">Cap otomatis mengikuti perusahaan penjual. TTD ini khusus untuk Penawaran Harga dari Permohonan.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Nama</label>
                        <input name="signature_name" required value="{{ old('signature_name', $signature?->nama ?: $penawaran->user?->name) }}"
                            @disabled(!$canEditQuotation)
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm disabled:bg-slate-50">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Jabatan</label>
                        <input name="signature_position" value="{{ old('signature_position', $signature?->jabatan) }}"
                            @disabled(!$canEditQuotation)
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm disabled:bg-slate-50">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Kota</label>
                        <input name="signature_city" value="{{ old('signature_city', $signature?->kota ?: 'Yogyakarta') }}"
                            @disabled(!$canEditQuotation)
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm disabled:bg-slate-50">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Tanggal</label>
                        <input type="date" name="signature_date" value="{{ old('signature_date', \Carbon\Carbon::parse($signature?->tanggal ?: now())->format('Y-m-d')) }}"
                            @disabled(!$canEditQuotation)
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm disabled:bg-slate-50">
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                    @if($signature?->ttd_path)
                        <div>
                            <div class="mb-1 text-xs font-semibold">TTD saat ini</div>
                            <img src="{{ asset('storage/'.$signature->ttd_path) }}" alt="TTD Penawaran"
                                class="h-24 rounded-lg border border-slate-200 bg-white p-2">
                        </div>
                    @endif
                    @if($canEditQuotation)
                        <div class="min-w-0 flex-1">
                            <label class="mb-1 block text-xs font-semibold">Import / Upload TTD</label>
                            <input type="file" name="signature_file" accept="image/png,image/jpeg,image/webp"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-violet-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-violet-700">
                            <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP; maksimal 2 MB.</p>
                        </div>
                    @endif
                </div>
            </section>

            @if($canEditQuotation)
                <div class="flex justify-end">
                    <button class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Simpan Penawaran Harga</button>
                </div>
            @endif
        </form>

        @if($canEditQuotation)
            <div class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="font-semibold text-emerald-900">Sudah selesai mengisi penawaran?</div>
                    <p class="mt-1 text-sm text-emerald-800">Simpan terlebih dahulu, periksa PDF, lalu kirim kepada pembeli.</p>
                </div>
                @if($grandTotal > 0)
                    <form method="POST" action="{{ route('penawaran-harga.kirim-penawaran', $usulan) }}">
                        @csrf
                        <button class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            {{ $usulan->penawaran_status === 'revision_requested' ? 'Kirim Ulang ke Pembeli' : 'Kirim ke Pembeli' }}
                        </button>
                    </form>
                @else
                    <button type="button" disabled class="cursor-not-allowed rounded-xl bg-slate-300 px-5 py-3 text-sm font-semibold text-slate-600">
                        Isi &amp; Simpan Harga Dulu
                    </button>
                @endif
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const formatter = new Intl.NumberFormat('id-ID');
            const rows = document.querySelectorAll('[data-quotation-item-row]');
            const subtotalNode = document.querySelector('[data-quotation-subtotal]');

            function refreshTotals() {
                let subtotal = 0;
                rows.forEach((row) => {
                    const qty = Number(row.querySelector('[data-quotation-qty]')?.value || 0);
                    const unitPrice = Number(row.querySelector('[data-quotation-unit-price]')?.value || 0);
                    const total = Math.round(qty * unitPrice);
                    subtotal += total;
                    const totalNode = row.querySelector('[data-quotation-row-total]');
                    if (totalNode) totalNode.textContent = `Rp ${formatter.format(total)}`;
                });
                if (subtotalNode) subtotalNode.textContent = `Rp ${formatter.format(subtotal)}`;
            }

            rows.forEach((row) => {
                row.querySelectorAll('[data-quotation-qty], [data-quotation-unit-price]').forEach((input) => {
                    input.addEventListener('input', refreshTotals);
                });
            });
        })();
    </script>
@endpush
