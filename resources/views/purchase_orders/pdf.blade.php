<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Pesanan Pembelian {{ $documentNumber }}</title>
    <style>
        @page {
            margin: 12mm 12mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.35;
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
            width: 150px;
            padding-right: 12px !important;
        }

        .logo {
            display: block;
            max-width: 140px;
            max-height: 62px;
        }

        .company-name {
            font-size: 13px;
            font-weight: 700;
        }

        .company-info {
            margin-top: 2px;
            font-size: 9px;
        }

        .document-title {
            margin: 14px 0 12px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta-table td {
            border: 0;
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 52px;
        }

        .meta-separator {
            width: 8px;
        }

        .meta-gap {
            width: 40px;
        }

        .meta-right-label {
            width: 55px;
        }

        .items-table {
            margin-top: 12px;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #111827;
            padding: 4px 5px;
            vertical-align: top;
        }

        .items-table th {
            background: #ffd966;
            text-align: center;
            font-weight: 700;
        }

        .no-col {
            width: 30px;
            text-align: center;
        }

        .qty-col {
            width: 48px;
            text-align: center;
        }

        .unit-col {
            width: 48px;
            text-align: center;
        }

        .money-col {
            width: 105px;
        }

        .item-title {
            font-weight: 400;
        }

        .details {
            margin: 1px 0 0 12px;
            padding: 0;
        }

        .details li {
            margin-bottom: 1px;
        }

        .money-inner td {
            border: 0 !important;
            padding: 0 !important;
            white-space: nowrap;
        }

        .money-value {
            text-align: right;
        }

        .totals-cell {
            text-align: right;
        }

        .final-row td {
            font-weight: 700;
        }

        .footer-table {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .footer-table > tr > td,
        .footer-table td.notes-cell,
        .footer-table td.signature-cell {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .signature-cell {
            width: 260px;
            text-align: center;
        }

        .notes-title {
            margin-bottom: 3px;
        }

        .notes-table td {
            border: 0;
            padding: 0 0 1px 0;
            vertical-align: top;
        }

        .notes-bullet {
            width: 12px;
        }

        /* Geometri cap dan TTD mengikuti dokumen Penawaran Harga: kotak 220x100,
           cap di tengah menimpa TTD dengan opacity 0,5. */
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
            font-weight: 700;
        }

        .signer-role {
            margin-top: 1px;
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
        $hasStamp = !empty($kop['stamp']) && is_file($kop['stamp']);
        $hasSignature = $signaturePath && is_file($signaturePath);
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
                    Telepon : {{ $kop['phone'] }}<br>
                    Email : {{ $kop['email'] }}
                </div>
            </td>
        </tr>
    </table>

    <div class="document-title">Pesanan Pembelian</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Kepada</td>
            <td class="meta-separator">:</td>
            <td>{{ $recipient['name'] }}</td>
            <td class="meta-gap"></td>
            <td class="meta-right-label">Nomer</td>
            <td class="meta-separator">:</td>
            <td>{{ $documentNumber }}</td>
        </tr>
        <tr>
            <td class="meta-label">Phone</td>
            <td class="meta-separator">:</td>
            <td>{{ $recipient['phone'] }}</td>
            <td class="meta-gap"></td>
            <td class="meta-right-label">Tanggal</td>
            <td class="meta-separator">:</td>
            <td>{{ $formatDate($documentDate) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Alamat</td>
            <td class="meta-separator">:</td>
            <td>{{ $recipient['address'] }}</td>
            <td colspan="4"></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="no-col" rowspan="2">No.</th>
                <th rowspan="2">Item</th>
                <th class="qty-col" rowspan="2">Jumlah</th>
                <th class="unit-col" rowspan="2">Satuan</th>
                <th class="money-col" colspan="2">Harga</th>
            </tr>
            <tr>
                <th class="money-col">Satuan</th>
                <th class="money-col">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr>
                    <td class="no-col">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-title">{{ $row['judul'] }}@if ($row['rincian']):@endif</div>
                        @if ($row['rincian'])
                            <ol class="details" type="a">
                                @foreach ($row['rincian'] as $rincian)
                                    <li>{{ $rincian }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </td>
                    <td class="qty-col">{{ $formatQuantity($row['jumlah']) }}</td>
                    <td class="unit-col">{{ $row['satuan'] }}</td>
                    <td class="money-col">
                        <table class="money-inner"><tr><td>Rp</td><td class="money-value">{{ number_format($row['harga_satuan'], 0, ',', '.') }}</td></tr></table>
                    </td>
                    <td class="money-col">
                        <table class="money-inner"><tr><td>Rp</td><td class="money-value">{{ number_format($row['total'], 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endforeach

            @foreach ($totals['lines'] as $line)
                <tr>
                    <td colspan="4" style="border-right:0"></td>
                    <td class="totals-cell">{{ $line['label'] }} :</td>
                    <td class="money-col">
                        <table class="money-inner"><tr><td>Rp</td><td class="money-value">{{ number_format($line['value'], 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endforeach

            <tr class="final-row">
                <td colspan="4" style="border-right:0"></td>
                <td class="totals-cell">{{ $totals['final_label'] }} :</td>
                <td class="money-col">
                    <table class="money-inner"><tr><td>Rp</td><td class="money-value">{{ number_format($totals['final_value'], 0, ',', '.') }}</td></tr></table>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Keterangan di kiri, tanda tangan di kanan -- keduanya sejajar seperti dokumen cetaknya. --}}
    <table class="footer-table">
        <tr>
            <td class="notes-cell">
                @if ($notes)
                    <div class="notes-title">Keterangan :</div>
                    <table class="notes-table">
                        @foreach ($notes as $note)
                            <tr>
                                <td class="notes-bullet">-</td>
                                <td>{{ $note }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
            <td class="signature-cell">
                <div>Yogyakarta, {{ $formatDate($documentDate) }}</div>
                <div>{{ $kop['name'] }}</div>
                <div class="signature-images" aria-hidden="true">
                    @if ($hasSignature)
                        <img class="signature" src="{{ $signaturePath }}" alt="Tanda tangan"
                            style="@if (!empty($signaturePlacement)) left: {{ $signaturePlacement['left'] }}px; top: {{ $signaturePlacement['top'] }}px; width: {{ $signaturePlacement['width'] }}px; height: {{ $signaturePlacement['height'] }}px; @else left: 50%; bottom: 0; transform: translateX(-50%); width: 100px; height: auto; @endif">
                    @endif
                    @if ($hasStamp)
                        <img class="stamp" src="{{ $kop['stamp'] }}" alt="Stempel perusahaan">
                    @endif
                </div>
                <div class="signer-name">{{ $signer['name'] }}</div>
                <div class="signer-role">{{ $signer['role'] }}</div>
            </td>
        </tr>
    </table>
</body>

</html>
