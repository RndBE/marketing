<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->docNumber?->doc_no }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            max-height: 80px;
        }

        .company-info {
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1a202c;
        }


        .info-table td {
            padding: 3px 0;
        }

        .label {
            font-weight: bold;
            width: 120px;
        }

        .items-table {
            width: 100%;
            border: 1px solid #333;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #65D1F2;
            padding: 10px;
            text-align: left;

            border-right: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .items-table td {
            border-right: 1px solid #333;
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 5px;
            padding-right: 5px;
            border-bottom: 1px solid #333;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 10px;
        }

        .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        thead th,
        tbody td {
            padding-top: 1px !important;
            padding-bottom: 1px !important;
        }
    </style>
</head>

<body>
    <table style="width:100%; border-collapse:collapse; border:0; margin:0;">
        <tr>
            <td style="width:180px; border:0 !important; padding:0 !important; vertical-align:middle;">
                <img src="{{ $kop['logo'] }}" style="width:170px; height:auto; display:block;">
            </td>
            <td style="border:0 !important; padding:0 0 0 12px !important; vertical-align:top;">
                <div style="font-size:14px; font-weight:700; line-height:1.1; margin:0;">
                    {{ $kop['nama'] }}
                </div>
                <div style="font-size:11px; line-height:1.25; margin-top:4px;">
                    {{ $kop['alamat'] }}
                </div>
                <div style="font-size:11px; line-height:1.25; margin-top:2px;">
                    Telepon: {{ $kop['telp'] }}
                </div>
                <div style="font-size:11px; line-height:1.25; margin-top:2px;">
                    Email : {{ $kop['email'] }}
                </div>
            </td>
        </tr>
    </table>

    <div style="border-top:2px solid #111; margin-top:8px;"></div>
    <div style="border-top:1px solid #111; margin-top:2px; margin-bottom:10px;"></div>
    <h3 style="text-align: center">{{ $invoice->parent ? $invoice->parent->judul : $invoice->judul }}</h3>
    <table
        style="width:100%; margin:0 auto 10px auto; border-collapse:collapse; font-size:12px; line-height:1.2; border:0;">
        <tr>
            <td style="width:50%; vertical-align:top; padding:0; border:0;">
                <table style="width:100%; border-collapse:collapse; border:0;">
                    <tr>
                        <td style="width:90px; padding:0 8px 2px 0; border:0;">Kepada</td>
                        <td style="width:10px; padding:0 8px 2px 0; border:0;">:</td>
                        <td style="padding:0 0 2px 0; border:0;">
                            <strong>{{ $invoice->user->name ?? 'Pelanggan' }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:90px; padding:0 8px 2px 0; border:0;">PIC</td>
                        <td style="width:10px; padding:0 8px 2px 0; border:0;">:</td>
                        <td style="padding:0 0 2px 0; border:0;">
                            {{ $invoice->pic->nama ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width:90px; padding:0 8px 2px 0; border:0;">Telp</td>
                        <td style="width:10px; padding:0 8px 2px 0; border:0;">:</td>
                        <td style="padding:0 0 2px 0; border:0;">
                            {{ $invoice->pic->no_hp ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>

            <td style="width:50%; vertical-align:top; padding:0; border:0;">
                <table style="width:100%; border-collapse:collapse; border:0;">
                    <tr>
                        <td style="width:160px; padding:0 8px 0px 0; text-align:right; border:0;">No. Invoice</td>
                        <td style="width:10px; padding:0 8px 0px 0; text-align:right; border:0;">:</td>-=
                        <td style="padding:0 0 0px 0; text-align:left; border:0;">
                            <strong>{{ $invoice->docNumber->doc_no }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">Tanggal Order</td>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">:</td>
                        <td style=" padding:0; text-align:left; border:0;">
                            {{ $invoice->tgl_invoice?->format('d M Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">Tanggal Jatuh Tempo</td>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">:</td>
                        <td style="padding:0; text-align:left; border:0;">
                            {{ $invoice->jatuh_tempo?->format('d M Y') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($invoice->parent)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
            <tr>
                <td style="font-weight: bold; ">Pembayaran ke -
                    {{ $invoice->parent->children()->where('id', '<=', $invoice->id)->count() }} :
                    {{ $invoice->judul }}</td>
            </tr>
        </table>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%" rowspan="2">NO</th>
                <th style="width: 45%; text-align: center; vertical-align: middle;" rowspan="2">URAIAN</th>
                <th style="width: 5%; text-align: center; vertical-align: middle;" rowspan="2">VOLUME</th>
                <th style="width: 5%; text-align: center; vertical-align: middle;" rowspan="2">SATUAN</th>
                <th style="width: 40%; text-align: center; vertical-align: middle;" colspan="2">HARGA</th>
            </tr>
            <tr>
                <th style="text-align: center;">SATUAN</th>
                <th style="text-align: center;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 1; @endphp
            @foreach ($invoice->items as $item)
                @if ($item->tipe === 'bundle')
                    <tr>
                        <td class="font-bold" style="text-align: center;">{{ $n++ }}</td>
                        <td class="font-bold">{{ $item->judul }}</td>
                        <td class="text-right">{{ $item->qty }}</td>
                        <td class="text-right">{{ $item->satuan }}</td>
                        <td class="font-bold" style="white-space: nowrap; padding:0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                                <tr style="border:none">
                                    <td align="left" style="border:none">Rp</td>
                                    <td align="right" style="border:none">
                                        {{ number_format($item->subtotal / max($item->qty, 1), 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="font-bold" style="white-space: nowrap; padding:0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                                <tr style="border:none">
                                    <td align="left" style="border:none">Rp</td>
                                    <td align="right" style="border:none">
                                        {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @foreach ($item->details as $d)
                        <tr style="color: #555;">
                            <td></td>
                            <td style="padding-left: 20px;">
                                {{ $d->nama }}
                                @if ($d->spesifikasi)
                                    <br><small>{{ $d->spesifikasi }}</small>
                                @endif
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach
                @else
                    @php
                        $isSingle = $item->details->count() === 1;
                    @endphp

                    @if ($isSingle)
                        @php $d = $item->details->first(); @endphp
                        <tr>
                            <td class="font-bold" style="text-align: center;">{{ $n++ }}</td>
                            <td class="font-bold">
                                {{ $item->judul }}
                                @if ($d->spesifikasi)
                                    <br><small style="font-weight:normal; color:#555;">{{ $d->spesifikasi }}</small>
                                @endif
                            </td>
                            <td class="" style="text-align: center;">{{ $d->qty }}</td>
                            <td class="" style="text-align: center;">{{ $d->satuan }}</td>
                            <td style="white-space: nowrap; padding:0;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                                    <tr style="border:none">
                                        <td align="left" style="border:none">Rp</td>
                                        <td align="right" style="border:none">
                                            {{ number_format($d->harga, 0, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </td>

                            <td class="font-bold" style="white-space: nowrap; padding:0;">

                                <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                                    <tr style="border:none">
                                        <td align="left" style="border:none">Rp</td>
                                        <td align="right" style="border:none">
                                            {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td class="font-bold">{{ $n++ }}</td>
                            <td class="font-bold">{{ $item->judul }}</td>
                            <td class="text-right"></td>
                            <td class="text-right"></td>
                            <td class="text-right font-bold" style="white-space: nowrap;">Rp
                                {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @foreach ($item->details as $d)
                            <tr style="color: #555;">
                                <td></td>
                                <td style="padding-left: 20px;">
                                    {{ $d->nama }}
                                    @if ($d->spesifikasi)
                                        <br><small>{{ $d->spesifikasi }}</small>
                                    @endif
                                </td>
                                <td class="text-right">{{ $d->qty }} {{ $d->satuan }}</td>
                                <td class="text-right" style="white-space: nowrap;">Rp
                                    {{ number_format($d->harga, 0, ',', '.') }}</td>
                                <td class="text-right" style="white-space: nowrap;">Rp
                                    {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endif
            @endforeach
        </tbody>

        <tr>
            <td colspan="5" class="text-right font-bold">Subtotal</td>
            <td class="font-bold" style="white-space: nowrap; padding:0;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                    <tr style="border:none">
                        <td align="left" style="border:none">Rp</td>
                        <td align="right" style="border:none">{{ number_format($invoice->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @if ($invoice->discount_amount > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Diskon</td>
                <td class="text-right font-bold text-red-600">Rp
                    {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if ($invoice->tax_amount > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Pajak ({{ $invoice->tax_percent }}%)</td>
                <td class="font-bold" style="white-space: nowrap; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                        <tr style="border:none">
                            <td align="left" style="border:none">Rp</td>
                            <td align="right" style="border:none">
                                {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        @endif
        <tr>
            <td colspan="5" class="text-right font-bold">Total</td>
            <td class="font-bold" style="white-space: nowrap; padding:0;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border:none">
                    <tr style="border:none">
                        <td align="left" style="border:none">Rp</td>
                        <td align="right" style="border:none">
                            {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

    <div
        style="margin-bottom: 20px; font-style: italic; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9;">
        <strong>Terbilang:</strong> {{ $invoice->terbilang }}
    </div>


    <table style="width: 100%; margin-top: 20px; border-collapse: collapse; border: 0;">
        <tr>

            <td style="width: 65%; vertical-align: top; padding-right: 20px; border: 0;">
                <div style="border-radius: 5px; font-size: 11px;">
                    @if ($invoice->payment_info)
                        <b>{!! nl2br(e($invoice->payment_info)) !!}</b>
                    @else
                        Bank: MCA (Mandiri Central Asia)<br>
                        A/N: CV. Arta Solusindo<br>
                        Rek: 123-456-7890
                    @endif

                    <br><br>
                    <strong>Keterangan:</strong><br>
                    @if ($invoice->terms->count() > 0)
                        <ol style="margin: 5px 0 0 15px; padding: 0;" type="1">
                            @foreach ($invoice->terms as $term)
                                <li style="margin-bottom: 3px;">{!! nl2br(e($term->isi)) !!}</li>
                            @endforeach
                        </ol>
                    @else
                        {{ $invoice->catatan ?? '-' }}
                    @endif
                </div>
            </td>


            <td style="width: 35%; vertical-align: top; text-align: center; border: 0;">
                <div style="margin-bottom: 20px;">
                    @if ($invoice->signature)
                        <div style="font-size: 10pt;">Hormat kami,</div>
                        <div style="font-size: 10pt; margin-top: 2px;">
                            {{ $invoice->signature->kota ?? 'Sleman' }},
                            {{ $invoice->signature->tanggal ? $invoice->signature->tanggal->isoFormat('D MMMM Y') : date('d F Y') }}
                        </div>

                        <div style="margin: 5px auto;">
                            @if ($invoice->signature->ttd_path)
                                <img src="{{ public_path('storage/' . $invoice->signature->ttd_path) }}"
                                    style="height: 60px; object-fit: contain;">
                            @else
                                <div style="height: 60px;"></div>
                            @endif
                        </div>

                        <div style="font-weight: bold; font-size: 10pt; text-decoration: underline;">
                            {{ $invoice->signature->nama }}</div>
                        <div style="font-size: 10pt;">{{ $invoice->signature->jabatan }}</div>
                    @else
                        <div style="font-size: 10pt;">Hormat kami,</div>
                        <br><br><br><br>
                        <div style="font-size: 10pt;">(..............................)</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
