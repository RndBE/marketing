<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Penawaran Harga {{ $quotationNumber }}</title>
    <style>
        @page {
            margin: 9mm 10mm 11mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .letterhead td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 155px;
            padding-right: 12px !important;
        }

        .logo {
            display: block;
            max-width: 145px;
            max-height: 66px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 700;
        }

        .company-info {
            margin-top: 2px;
            font-size: 9.5px;
            line-height: 1.3;
        }

        .header-rule {
            height: 4px;
            margin-top: 7px;
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
        }

        .document-title {
            margin: 10px 0 9px;
            text-align: center;
            font-size: 13.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta-table {
            margin-bottom: 9px;
        }

        .meta-table td {
            border: 0;
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 68px;
            font-weight: 700;
        }

        .meta-separator {
            width: 10px;
        }

        .meta-gap {
            width: 28px;
        }

        .meta-right-label {
            width: 103px;
            font-weight: 700;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #374151;
            padding: 4px 4px;
            vertical-align: top;
        }

        .items-table th {
            background: #e5e7eb;
            text-align: center;
            font-size: 8px;
            line-height: 1.15;
        }

        .no-col {
            width: 28px;
            text-align: center;
        }

        .product-col {
            width: 67px;
            text-align: center;
        }

        .volume-col {
            width: 55px;
            text-align: center;
        }

        .money-col {
            width: 91px;
        }

        .item-title {
            font-weight: 700;
        }

        .item-note {
            margin-top: 2px;
            color: #374151;
            white-space: pre-line;
        }

        .details {
            margin: 3px 0 0 13px;
            padding: 0;
        }

        .details li {
            margin-bottom: 1px;
        }

        .money {
            white-space: nowrap;
        }

        .money-inner td {
            border: 0 !important;
            padding: 0 !important;
        }

        .totals-label {
            text-align: right;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid #111827;
            font-weight: 700;
        }

        .notes-section {
            margin-top: 12px;
        }

        .notes-title {
            margin-bottom: 3px;
            font-weight: 700;
        }

        .notes-list {
            margin: 0;
            padding-left: 15px;
        }

        .notes-list li {
            margin-bottom: 2px;
        }

        .signature-wrap {
            width: 270px;
            margin-top: 20px;
            margin-left: auto;
            text-align: center;
            page-break-inside: avoid;
        }

        /* Geometri cap dan TTD mengikuti dokumen Penawaran Harga biasa:
           kotak 220x100, cap di tengah menimpa TTD dengan opacity 0,5. */
        .signature-images {
            position: relative;
            width: 220px;
            height: 100px;
            margin: 1px auto 0;
        }

        .stamp {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            opacity: .5;
            z-index: 2;
        }

        .signature {
            position: absolute;
            z-index: 1;
        }

        .signer-name {
            display: inline-block;
            min-width: 170px;
            padding-bottom: 1px;
            border-bottom: 1px solid #111827;
            font-weight: 700;
        }

        .signer-role {
            margin-top: 2px;
            font-size: 9.5px;
        }
    </style>
</head>

<body>
    @php
        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $formatDate = static fn ($date): string => sprintf(
            '%02d %s %d',
            $date->day,
            $indonesianMonths[$date->month],
            $date->year
        );
        $formatQuantity = static function ($quantity): string {
            $number = (float) $quantity;

            return fmod($number, 1.0) === 0.0
                ? number_format($number, 0, ',', '.')
                : rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
        };
        $subtotal = $penawaran->calcItemsSubtotal();
        $discount = $penawaran->calcDiscountAmount();
        $tax = $penawaran->calcTaxAmount();
        $grandTotal = $penawaran->calcGrandTotal();
        $discountLabel = ($penawaran->discount_type ?? 'percent') === 'percent'
            ? rtrim(rtrim(number_format((float) $penawaran->discount_value, 2, ',', '.'), '0'), ',').' %'
            : 'Nominal';
        $signerName = $signature?->nama ?: $penawaran->user?->name ?: '-';
        $signerRole = $signature?->jabatan ?: ($penawaran->user?->roles?->pluck('name')->implode(', ') ?: 'Staff');
        $signatureCity = $signature?->kota ?: 'Yogyakarta';
        $signatureDate = $signature?->tanggal
            ? \Carbon\Carbon::parse($signature->tanggal)
            : $quotationDate;
    @endphp

    <table class="letterhead">
        <tr>
            <td class="logo-cell">
                @if (!empty($kop['logo']) && is_file($kop['logo']))
                    <img class="logo" src="{{ $kop['logo'] }}" alt="Logo {{ $kop['name'] }}">
                @endif
            </td>
            <td>
                <div class="company-name">{{ $kop['name'] }}</div>
                <div class="company-info">
                    {{ $kop['address'] }}<br>
                    Telepon: {{ $kop['phone'] }} / Email: {{ $kop['email'] }}
                </div>
            </td>
        </tr>
    </table>

    <div class="header-rule"></div>
    <div class="document-title">PENAWARAN HARGA {{ $penawaran->judul }}</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Kepada</td>
            <td class="meta-separator">:</td>
            <td><strong>{{ $usulan->company?->name ?: '-' }}</strong></td>
            <td class="meta-gap"></td>
            <td class="meta-right-label">No. Surat Permohonan</td>
            <td class="meta-separator">:</td>
            <td><strong>{{ $requestDocumentNumber }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Pekerjaan</td>
            <td class="meta-separator">:</td>
            <td>{{ $penawaran->nama_pekerjaan ?: $penawaran->judul }}</td>
            <td class="meta-gap"></td>
            <td class="meta-right-label">Tanggal Permohonan</td>
            <td class="meta-separator">:</td>
            <td>{{ $formatDate($requestDate) }}</td>
        </tr>
        <tr>
            <td class="meta-label">No. Penawaran</td>
            <td class="meta-separator">:</td>
            <td>{{ $quotationNumber }}</td>
            <td colspan="4"></td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-separator">:</td>
            <td>{{ $formatDate($quotationDate) }}</td>
            <td colspan="4"></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="no-col">NO.</th>
                <th>ITEM DAN URAIAN / SPESIFIKASI / PEKERJAAN</th>
                <th class="product-col">PRODUCT<br>NO.</th>
                <th class="volume-col">VOLUME /<br>SATUAN</th>
                <th class="money-col">HARGA<br>SATUAN</th>
                <th class="money-col">HARGA<br>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($penawaran->items as $index => $item)
                @php
                    $visibleDetails = $item->details->reject(function ($detail) use ($item) {
                        return $item->details->count() === 1
                            && trim((string) $detail->nama) === trim((string) $item->judul)
                            && blank($detail->spesifikasi);
                    });
                    $unitPrice = $item->calcUnitSubtotal();
                    $itemTotal = $item->calcSubtotal();
                    $requestPoints = preg_split('/\R/', (string) $item->catatan, -1, PREG_SPLIT_NO_EMPTY);
                @endphp
                <tr>
                    <td class="no-col">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-title">{{ $item->judul }}</div>
                        @if ($requestPoints || $visibleDetails->isNotEmpty())
                            <ol class="details" type="a">
                                @foreach ($requestPoints as $point)
                                    <li>{{ trim($point) }}</li>
                                @endforeach
                                @foreach ($visibleDetails as $detail)
                                    <li>
                                        {{ $detail->nama }}
                                        @if ($detail->spesifikasi)
                                            - {{ $detail->spesifikasi }}
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </td>
                    <td class="product-col">{{ $item->product?->kode ?: '-' }}</td>
                    <td class="volume-col">
                        {{ $formatQuantity($item->resolvedQty()) }}<br>
                        {{ $item->satuan ?: 'Paket' }}
                    </td>
                    <td class="money-col money">
                        <table class="money-inner"><tr><td>Rp</td><td style="text-align:right">{{ number_format($unitPrice, 0, ',', '.') }}</td></tr></table>
                    </td>
                    <td class="money-col money">
                        <table class="money-inner"><tr><td>Rp</td><td style="text-align:right">{{ number_format($itemTotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" class="totals-label">JUMLAH</td>
                <td class="money-col money">
                    <table class="money-inner"><tr><td>Rp</td><td style="text-align:right">{{ number_format($subtotal, 0, ',', '.') }}</td></tr></table>
                </td>
            </tr>
            @if ($penawaran->discount_enabled && $discount > 0)
                <tr>
                    <td colspan="5" class="totals-label">DISKON {{ $discountLabel }}</td>
                    <td class="money-col money">
                        <table class="money-inner"><tr><td>Rp</td><td style="text-align:right">{{ number_format($discount, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endif
            @if ($penawaran->tax_enabled && $tax > 0)
                <tr>
                    <td colspan="5" class="totals-label">PPN {{ rtrim(rtrim(number_format((float) $penawaran->tax_rate, 2, ',', '.'), '0'), ',') }} %</td>
                    <td class="money-col money">
                        <table class="money-inner"><tr><td>Rp</td><td style="text-align:right">{{ number_format($tax, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="5" class="totals-label">TOTAL HARGA</td>
                <td class="money-col money">
                    <table class="money-inner"><tr><td>Rp</td><td style="text-align:right">{{ number_format($grandTotal, 0, ',', '.') }}</td></tr></table>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="notes-section">
        <div class="notes-title">Keterangan:</div>
        <ul class="notes-list">
            @if ($penawaran->catatan)
                <li>{{ $penawaran->catatan }}</li>
            @endif
            @foreach ($penawaran->terms as $term)
                <li>
                    @if ($term->judul)<strong>{{ $term->judul }}:</strong> @endif{{ $term->isi }}
                </li>
            @endforeach
            @if ($penawaran->validity?->sampai)
                <li>Harga berlaku sampai {{ $formatDate(\Carbon\Carbon::parse($penawaran->validity->sampai)) }}.</li>
            @elseif ($penawaran->validity?->keterangan)
                <li>{{ $penawaran->validity->keterangan }}</li>
            @endif
            @if (!$penawaran->tax_enabled)
                <li>Harga belum termasuk pajak.</li>
            @endif
        </ul>
    </div>

    <div class="signature-wrap">
        <div>{{ $signatureCity }}, {{ $formatDate($signatureDate) }}</div>
        <div>{{ $kop['name'] }}</div>
        @php
            $hasStamp = !empty($kop['stamp']) && is_file($kop['stamp']);
            $hasSignature = $signaturePath && is_file($signaturePath);
        @endphp
        <div class="signature-images" aria-hidden="true">
            @if ($hasSignature)
                {{-- Tanpa hasil hitung centroid, posisinya jatuh ke pola Penawaran Harga
                     biasa: tengah secara horizontal dan rapat ke dasar kotak. --}}
                <img class="signature" src="{{ $signaturePath }}" alt="Tanda tangan"
                    style="@if (!empty($signaturePlacement)) left: {{ $signaturePlacement['left'] }}px; top: {{ $signaturePlacement['top'] }}px; width: {{ $signaturePlacement['width'] }}px; height: {{ $signaturePlacement['height'] }}px; @else left: 50%; bottom: 0; transform: translateX(-50%); width: 100px; height: auto; @endif">
            @endif
            @if ($hasStamp)
                <img class="stamp" src="{{ $kop['stamp'] }}" alt="Stempel perusahaan">
            @endif
        </div>
        <div class="signer-name">{{ $signerName }}</div>
        <div class="signer-role">{{ $signerRole }}</div>
    </div>
</body>

</html>
