<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $docNo }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.35
        }

        .page-break {
            page-break-after: always
        }

        .muted {
            color: #555
        }

        .h1 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 6px
        }

        .h2 {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 6px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            border: 1px solid black;
            padding: 7px;
            vertical-align: top
        }

        th {
            background: #65D1F2
        }

        ol {
            margin: 0;

            padding-left: 18px
        }

        li {
            margin: 0 0 4px 0
        }

        .tight {
            margin: 0
        }

        thead th {
            text-align: center;
            vertical-align: middle;
        }

        thead th,
        tbody td {
            padding-top: 1px !important;
            padding-bottom: 1px !important;
        }
    </style>
</head>

<body>
    @php
        $cover = $penawaran->cover;
        $valid = $penawaran->validity;
        $grand = 0;
        foreach ($penawaran->items as $it) {
            $grand += (int) $it->subtotal;
        }

        $discountAmount = 0;

        if ($penawaran->discount_enabled) {
            $dv = (float) ($penawaran->discount_value ?? 0);
            $dt = $penawaran->discount_type ?? 'percent';

            if ($dt === 'percent') {
                $discountAmount = (int) round($grand * ($dv / 100));
            } else {
                $discountAmount = (int) round($dv);
            }

            if ($discountAmount > $grand) {
                $discountAmount = $grand;
            }
        }

        $dpp = $grand - $discountAmount;

        $taxAmount = 0;
        if ($penawaran->tax_enabled) {
            $tr = (float) ($penawaran->tax_rate ?? 11);
            $taxAmount = (int) round($dpp * ($tr / 100));
        }

        $grandTotal = $dpp + $taxAmount;
    @endphp

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
    <h3 style="text-align: center">{{ $penawaran->judul }}</h3>
    <table
        style="width:100%; margin:0 auto 10px auto; border-collapse:collapse; font-size:12px; line-height:1.2; border:0;">
        <tr>
            <td style="width:60%; vertical-align:top; padding:0; border:0;">
                <table style="width:100%; border-collapse:collapse; border:0;">
                    <tr>
                        <td style="width:90px; padding:0 8px 2px 0; border:0;">Kepada</td>
                        <td style="width:10px; padding:0 8px 2px 0; border:0;">:</td>
                        <td style="padding:0 0 2px 0; border:0;">
                            <strong>{{ $penawaran->instansi_tujuan ?? ($penawaran->pic?->instansi ?? '-') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:90px; padding:0 8px 2px 0; border:0;">PIC</td>
                        <td style="width:10px; padding:0 8px 2px 0; border:0;">:</td>
                        <td style="padding:0 0 2px 0; border:0;">
                            <strong>
                                {{ $penawaran->pic?->nama ?? '-' }}
                                @if ($penawaran->pic?->no_hp)
                                    / {{ $penawaran->pic->no_hp }}
                                @endif
                            </strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 8px 2px 0; border:0;">Pekerjaan</td>
                        <td style="padding:0 8px 2px 0; border:0;">:</td>
                        <td style="padding:0 0 2px 0; border:0;">
                            {{ $penawaran->nama_pekerjaan ?? '-' }}
                        </td>
                    </tr>

                </table>
            </td>

            <td style="width:40%; vertical-align:top; padding:0; border:0;">
                <table style="width:100%; border-collapse:collapse; border:0;">
                    <tr>
                        <td style="width:110px; padding:0 8px 2px 0; text-align:right; border:0;">No. Penawaran</td>
                        <td style="width:10px; padding:0 8px 2px 0; text-align:right; border:0;">:</td>
                        <td style="padding:0 0 2px 0; text-align:left; border:0;">
                            <strong>{{ $docNo }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">Tanggal</td>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">:</td>
                        <td style="padding:0; text-align:left; border:0;">
                            {{ $penawaran->tanggal_penawaran
                                ? $penawaran->tanggal_penawaran->locale('id')->isoFormat('dddd D MMMM YYYY')
                                : $penawaran->created_at->locale('id')->isoFormat('dddd D MMMM YYYY') }}

                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">Lokasi</td>
                        <td style="padding:0 8px 0 0; text-align:right; border:0;">:</td>
                        <td style="padding:0; text-align:left; border:0;">
                            {{ $penawaran->lokasi_pekerjaan ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>




    <table style="margin-top:8px;width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th style="width:5%" class="right" rowspan="2">NO</th>
                <th style="width:60%" rowspan="2">ITEM DAN URAIAN SPESIFIKASI PEKERJAAN</th>
                <th style="width:15%" rowspan="2">VOLUME/SATUAN</th>
                <th style="width:20%" colspan="2" class="right">Harga</th>
            </tr>
            <tr>
                <th class="right">Satuan</th>
                <th class="right">Total</th>
            </tr>
        </thead>

        <tbody>
            @php $grand = 0; @endphp

            @foreach ($penawaran->items as $i => $item)
                @php
                    $detailCount = $item->details ? $item->details->count() : 0;

                    $volume = (float) ($item->qty ?? 1);

                    $hargaSatuanBundle = 0;
                    $totalItem = 0;

                    if ($detailCount) {
                        foreach ($item->details as $d) {
                            $qtyD = (float) ($d->qty ?? 1);
                            $hargaD = (int) ($d->harga ?? 0);
                            $subtotalD = (int) ($d->subtotal ?? 0);

                            if ($hargaD <= 0 && $subtotalD > 0 && $qtyD > 0) {
                                $hargaD = (int) round($subtotalD / $qtyD);
                            }

                            $hargaSatuanBundle += $hargaD;
                        }
                    }

                    if ($hargaSatuanBundle <= 0) {
                        $hargaSatuanBundle = (int) ($item->subtotal ?? 0);
                        $totalItem = $hargaSatuanBundle;
                    } else {
                        $totalItem = (int) round($hargaSatuanBundle * $volume);
                    }

                    $grand += $totalItem;
                @endphp

                <tr>
                    <td class="right" style="text-align: center;margin-bottom:0px">{{ $i + 1 }}</td>

                    <td>
                        <div><strong>{{ $item->judul }}</strong></div>

                        @if (!empty($item->catatan))
                            <div class="muted" style="margin-top:4px;text-align:left">
                                {{ $item->catatan }}
                            </div>
                        @endif

                        @if ($detailCount)
                            <ol style="margin:6px 0 0 10px;padding:0;" type="a">
                                @foreach ($item->details as $d)
                                    <li style="margin:0 0 0px 0;">
                                        <div class="tight">
                                            {{ $d->nama }}
                                            @if (!empty($d->spesifikasi))
                                                <span class="muted"> — {{ $d->spesifikasi }}</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="muted">-</div>
                        @endif
                    </td>

                    <td style="text-align:center;white-space:nowrap">
                        {{ number_format($volume, 2, ',', '.') }}
                    </td>

                    <td class="right" style="white-space:nowrap">
                        Rp {{ number_format((int) $hargaSatuanBundle, 0, ',', '.') }}
                    </td>

                    <td class="right" style="white-space:nowrap">
                        Rp {{ number_format((int) $totalItem, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            <tr>
                <td colspan="4" style="text-align:right"><strong>
                        {{ !$penawaran->tax_enabled ? 'Total Harga belum termasuk PPN' : 'Total Harga' }}
                    </strong></td>
                <td class="right" style="white-space:nowrap">
                    <strong>Rp {{ number_format((int) $grand, 0, ',', '.') }}</strong>
                </td>
            </tr>

            @if ($penawaran->discount_enabled && $discountAmount > 0)
                <tr>
                    <td colspan="4" style="text-align:right">
                        <strong>Diskon</strong>
                    </td>
                    <td class="right" style="white-space:nowrap">
                        <strong>- Rp {{ number_format((int) $discountAmount, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            @endif

            @if ($penawaran->tax_enabled && $taxAmount > 0)
                <tr>
                    <td colspan="4" style="text-align:right">
                        <strong>Pajak
                            ({{ number_format((float) ($penawaran->tax_rate ?? 11), 2, ',', '.') }}%)</strong>
                    </td>
                    <td class="right" style="white-space:nowrap">
                        <strong>Rp {{ number_format((int) $taxAmount, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            @endif
            @if ($penawaran->tax_enabled)
                <tr>
                    <td colspan="4" style="text-align:right"><strong>Total</strong></td>
                    <td class="right" style="white-space:nowrap">
                        <strong>Rp {{ number_format((int) $grandTotal, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            @endif
        </tbody>


    </table>

    @if ($penawaran->terms && $penawaran->terms->count())
        <table style="width:100%; border-collapse:collapse; border:0; margin-top:14px;">
            <tr>
                <td style="width:70%; vertical-align:top; border:0; padding:0;">
                    <div style="margin:0; padding:0;">Keterangan :</div>

                    <ul style="margin:0; padding:0; list-style:none;">
                        @php
                            $terms = $penawaran->terms;

                            $termsByParent = $terms->groupBy('parent_id');

                            $renderTerms = function ($parentId, $level = 0) use (&$renderTerms, $termsByParent) {
                                $items = $termsByParent[$parentId] ?? collect();

                                foreach ($items->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $term) {
                                    echo '<div style="margin-left:' . $level * 8 . 'px;">';

                                    if ($level == 0) {
                                        echo '- ' . e($term->isi);
                                    } else {
                                        echo '> ' . e($term->isi);
                                    }

                                    echo '</div>';

                                    $renderTerms($term->id, $level + 1);
                                }
                            };
                        @endphp

                        <div style="margin-top:4px; font-size:8pt; line-height:1.1;">
                            {!! $renderTerms(null, 0) !!}
                        </div>
                    </ul>
                </td>

                <td style="width:30%; vertical-align:top; border:0; padding:0; text-align:center;">
                    <div style="margin:0; padding:0; text-align:center;">
                        @foreach ($penawaran->signatures as $sg)
                            <div style="font-size:9pt;margin:0; padding:0;">Hormat kami,</div>
                            <div style="font-size:9pt; margin:2px 0 0 0;">
                                {{ $sg->kota }},
                                {{ $penawaran->tanggal_penawaran
                                    ? $penawaran->tanggal_penawaran->locale('id')->isoFormat('D MMMM YYYY')
                                    : $penawaran->created_at->locale('id')->isoFormat('D MMMM YYYY') }}
                            </div>

                            <div style="text-align:center; margin-top:4px;">
                                <div style="position:relative; width:220px; height:100px; margin:0 auto;">

                                    @php
                                        $ttdPath = $sg->ttd_path
                                            ? public_path('storage/' . ltrim($sg->ttd_path, '/'))
                                            : null;

                                        $stampPath = public_path('images/cap_arsol.png');
                                    @endphp

                                    {{-- TTD --}}
                                    @if ($ttdPath && file_exists($ttdPath))
                                        <img src="{{ $ttdPath }}"
                                            style="
                position:absolute;
                left:50%;
                bottom:0;
                transform:translateX(-50%);
                width:100px;
                height:auto;
                z-index:1;
            ">
                                    @endif

                                    {{-- STEMPEL --}}
                                    @if (file_exists($stampPath))
                                        <img src="{{ $stampPath }}"
                                            style="
                position:absolute;
                left:50%;
                top:50%;
                transform:translate(-50%, -50%);
                width:220px;
                opacity:0.65;
                z-index:2;
            ">
                                    @endif

                                </div>


                                <div style="font-size:10pt; font-weight:700; margin:0;text-decoration:underline">
                                    {{ $sg->nama }}</div>

                                <div style="font-size:9pt; margin:2px 0 0 0;">
                                    {{ $sg->jabatan }}</div>
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
        </table>
    @endif


</body>

</html>
