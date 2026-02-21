@php
    $defaultTitle = "Dokumen\nPenawaran Harga\nTelemetri";
    $titleText = trim((string) ($cover?->judul_cover ?? ''));
    $coverTitle = $defaultTitle;

    $subtitleText = trim((string)  ($penawaran->judul ?? ''));
    $coverSubtitle = $subtitleText !== '' ? $subtitleText : '-';

    $pillText = trim((string) ($penawaran->nama_pekerjaan ?? ''));
    $coverPill = $pillText !== '' ? $pillText : '-';

    $clientText = trim((string) ($penawaran->instansi_tujuan ?? ($penawaran->pic?->instansi ?? '')));
    $coverClient = $clientText !== '' ? $clientText : '-';

    $dateSource = $penawaran->tanggal_penawaran ?: $penawaran->created_at;
    $coverDate = $dateSource ? $dateSource->locale('id')->isoFormat('MMMM YYYY') : '';

    $coverPhotoPathJpg = public_path('templates/penawaran-cover-photo.jpg');
    $coverPhotoPathPng = public_path('templates/penawaran-cover-photo.png');
    $coverPhoto = is_file($coverPhotoPathJpg)
        ? $coverPhotoPathJpg
        : (is_file($coverPhotoPathPng)
            ? $coverPhotoPathPng
            : null);
    $badgeLogo = public_path('templates/badge.png');
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css');

    .cover-page {
        position: relative;
        width: 210mm;
        height: 297mm;
        margin: 0;
        overflow: hidden;
        page-break-after: always;
        font-family: 'Montserrat', Arial, Helvetica, sans-serif;
    }

    .cover-photo {
        position: absolute;
        top: 0;
        left: 0;
        width: 210mm;
        height: 110mm;
    }

    .cover-photo-fallback {
        background: #e5e7eb;
    }

    .cover-badge {
        position: absolute;
        right: 12mm;
        top: 86mm;
        width: 70mm;
        height: 70mm;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    .cover-badge img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .cover-content {
        position: absolute;
        left: 12mm;
        top: 116mm;
        width: 170mm;
    }

    .cover-date {
        font-size: 12pt;
        color: #9aa0a6;
        margin-bottom: 3px;
    }

    .cover-title {
        font-size: 30pt;
        font-weight: bold;
        line-height: 1.2;
    }

    .cover-subtitle {
        width: 120mm;
        font-size: 18pt;
        font-weight: bold;
        margin-top: 4mm;
    }

    .cover-line {
        width: 16mm;
        height: 1mm;
        border-radius: 10px;
        background: #111;
        margin-top: 4mm;
    }

    .cover-pill {
        width: 186mm;
        display: block;
        text-align: center;
        margin-top: 8mm;
        padding-top: 4mm;
        padding-bottom: 4mm;
        border-radius: 999px;
        background: #e3d2a8;
        font-weight: normal;
        font-size: 16pt;
        box-sizing: border-box;
    }

    .cover-client {
        margin-top: 3mm;
        font-size: 16pt;
        font-weight: 700;
    }

    .cover-footer {
        position: absolute;
        left: 12mm;
        bottom: 20mm;
        width: 186mm;
        text-align: right;
        font-size: 12pt;
    }

    .cover-footer .footer-item {
        margin-left: 4mm;
        display: inline-table;
        white-space: nowrap;
        vertical-align: middle;
    }

    .cover-footer .footer-icon {
        display: table-cell;
        vertical-align: middle;
        width: 12px;
    }

    .cover-footer .footer-text {
        display: table-cell;
        vertical-align: middle;
        padding-left: 1mm;
        padding-bottom: 3px;
    }

    .cover-footer .footer-icon-img {
        width: 12px;
        height: 12px;
        object-fit: contain;
        display: block;
    }
</style>
<div class="cover-page">
    @if ($coverPhoto)
        <img class="cover-photo" src="{{ $coverPhoto }}" alt="Foto Cover">
    @else
        <div class="cover-photo cover-photo-fallback"></div>
    @endif

    <div class="cover-badge">
        <img src="{{ $badgeLogo }}" alt="Logo">
    </div>

    <div class="cover-content">
        @if ($coverDate)
            <table
                style="border: none; margin: 0; border-collapse: collapse; vertical-align: middle; width: auto; table-layout: auto;">
                <th
                    style="background-color: white; border: none; text-align: center; vertical-align: middle; width: 1%; white-space: nowrap;">
                    <img src="{{ public_path('templates/arrow.png') }}" style="width:20px" alt="Logo">
                </th>
                <th
                    style="background-color: white; border: none; text-align: center; vertical-align: middle; width: 1%; white-space: nowrap;">
                    <div class="cover-date">{{ $coverDate }}</div>
                </th>
            </table>
        @endif
        <div class="cover-title">{!! nl2br(e($coverTitle)) !!}</div>
        <div class="cover-subtitle">{{ $coverSubtitle }}</div>
        <div class="cover-line"></div>
        <div class="cover-pill">{{ $coverPill }}</div>
        <div class="cover-client">{{ $coverClient }}</div>
    </div>

    <div class="cover-footer">
        <span class="footer-item"><span class="footer-icon"><img src="{{ public_path('templates/email.png') }}"
                    alt="" class="footer-icon-img"></span><span
                class="footer-text">{{ $cover?->perusahaan_email ?? 'cv.artasolusindo@gmail.com' }}</span></span>
        <span class="footer-item"><span class="footer-icon"><img src="{{ public_path('templates/wa.png') }}"
                    alt="" class="footer-icon-img"></span><span
                class="footer-text">{{ $cover?->perusahaan_telp ?? '085727868505' }}</span></span>
    </div>
    <div class="ornamen-footer" style="position: absolute; left: 0; bottom: 0; width: 210mm;">
        <img src="{{ public_path('templates/ornamen_bawah.png') }}"
            style="width: 210mm; height: auto; object-fit: contain; display: block;" alt="Logo">
    </div>
</div>
