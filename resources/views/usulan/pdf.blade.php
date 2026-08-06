<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Permohonan Penawaran {{ $documentNumber }}</title>
    <style>
        @page {
            margin: 10mm 12mm 13mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
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
            width: 155px;
            padding-right: 12px !important;
        }

        .logo {
            display: block;
            max-width: 145px;
            max-height: 62px;
        }

        .company-name {
            margin-bottom: 2px;
            font-size: 14px;
            font-weight: 700;
        }

        .company-line {
            font-size: 9.5px;
            line-height: 1.3;
        }

        .header-rule {
            margin-top: 7px;
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 4px;
        }

        .document-title {
            margin: 11px 0 12px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .meta-table {
            margin-bottom: 12px;
        }

        .meta-table td {
            border: 0;
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 53px;
            font-weight: 700;
        }

        .meta-separator {
            width: 10px;
        }

        .meta-gap {
            width: 26px;
        }

        .meta-right-label {
            width: 47px;
            font-weight: 700;
        }

        .items-table {
            page-break-inside: auto;
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
            padding: 5px 6px;
            vertical-align: top;
        }

        .items-table th {
            background: #facc15;
            text-align: center;
            font-weight: 700;
        }

        .number-column {
            width: 34px;
            text-align: center;
        }

        .quantity-column {
            width: 76px;
            text-align: center;
        }

        .unit-column {
            width: 80px;
            text-align: center;
        }

        .item-title {
            font-weight: 700;
        }

        .item-note {
            margin-top: 3px;
            color: #374151;
            font-size: 9.5px;
            white-space: pre-line;
        }

        .item-points {
            margin: 3px 0 0 15px;
            padding: 0;
            color: #374151;
            font-size: 9.5px;
        }

        .item-points li {
            margin-bottom: 1px;
        }

        .description {
            margin-top: 10px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            page-break-inside: avoid;
        }

        .description-title {
            margin-bottom: 3px;
            font-weight: 700;
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
        $formattedDate = sprintf('%02d %s %d', $documentDate->day, $indonesianMonths[$documentDate->month], $documentDate->year);
        $formatQuantity = static function ($quantity): string {
            $number = (float) $quantity;

            return fmod($number, 1.0) === 0.0
                ? number_format($number, 0, ',', '.')
                : rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
        };
        $recipientPhone = $usulan->pic?->no_hp ?: $usulan->targetCompany?->phone;
        $recipientAddress = $usulan->pic?->alamat ?: $usulan->targetCompany?->address;
        $signerName = $usulan->signature_name ?: $usulan->creator?->name ?: '-';
        $signerRole = $usulan->signature_position
            ?: ($usulan->creator?->roles?->pluck('name')->implode(', ') ?: 'Pemohon');
        $signatureCity = $usulan->signature_city ?: 'Yogyakarta';
        $signatureDate = $usulan->signature_date ?: $documentDate;
        $formattedSignatureDate = sprintf(
            '%02d %s %d',
            $signatureDate->day,
            $indonesianMonths[$signatureDate->month],
            $signatureDate->year
        );
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
                <div class="company-line">{{ $kop['address'] }}</div>
                <div class="company-line">Telepon: {{ $kop['phone'] }}</div>
                <div class="company-line">Email: {{ $kop['email'] }}</div>
            </td>
        </tr>
    </table>

    <div class="header-rule"></div>
    <div class="document-title">PERMOHONAN PENAWARAN</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Kepada</td>
            <td class="meta-separator">:</td>
            <td><strong>{{ $usulan->targetCompany?->name ?: '-' }}</strong></td>
            <td class="meta-gap"></td>
            <td class="meta-right-label">Nomor</td>
            <td class="meta-separator">:</td>
            <td><strong>{{ $documentNumber }}</strong></td>
        </tr>
        @if ($usulan->pic)
            <tr>
                <td class="meta-label">U.p.</td>
                <td class="meta-separator">:</td>
                <td>{{ $usulan->pic->nama }}{{ $usulan->pic->jabatan ? ' - '.$usulan->pic->jabatan : '' }}</td>
                <td class="meta-gap"></td>
                <td class="meta-right-label">Tanggal</td>
                <td class="meta-separator">:</td>
                <td>{{ $formattedDate }}</td>
            </tr>
        @else
            <tr>
                <td class="meta-label">Phone</td>
                <td class="meta-separator">:</td>
                <td>{{ $recipientPhone ?: '-' }}</td>
                <td class="meta-gap"></td>
                <td class="meta-right-label">Tanggal</td>
                <td class="meta-separator">:</td>
                <td>{{ $formattedDate }}</td>
            </tr>
        @endif
        @if ($usulan->pic)
            <tr>
                <td class="meta-label">Phone</td>
                <td class="meta-separator">:</td>
                <td>{{ $recipientPhone ?: '-' }}</td>
                <td colspan="4"></td>
            </tr>
        @endif
        <tr>
            <td class="meta-label">Alamat</td>
            <td class="meta-separator">:</td>
            <td colspan="5">{{ $recipientAddress ?: '-' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="number-column">No.</th>
                <th>Item</th>
                <th class="quantity-column">Jumlah</th>
                <th class="unit-column">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usulan->items as $index => $item)
                @php
                    $itemPoints = preg_split('/\R/', (string) $item->catatan, -1, PREG_SPLIT_NO_EMPTY);
                @endphp
                <tr>
                    <td class="number-column">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-title">{{ $item->judul }}</div>
                        @if ($itemPoints)
                            <ol class="item-points" type="a">
                                @foreach ($itemPoints as $point)
                                    <li>{{ trim($point) }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </td>
                    <td class="quantity-column">{{ $formatQuantity($item->qty) }}</td>
                    <td class="unit-column">{{ $item->satuan ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="number-column">1</td>
                    <td>{{ $usulan->judul }}</td>
                    <td class="quantity-column">1</td>
                    <td class="unit-column">Paket</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($usulan->deskripsi)
        <div class="description">
            <div class="description-title">Keterangan kebutuhan</div>
            <div>{!! nl2br(e($usulan->deskripsi)) !!}</div>
        </div>
    @endif

    <div class="signature-wrap">
        <div>{{ $signatureCity }}, {{ $formattedSignatureDate }}</div>
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
