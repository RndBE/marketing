@php
    $defaultTitle = "Dokumen\nPenawaran Harga\nTelemetri";
    $titleText = trim((string) ($cover?->judul_cover ?? ''));
    $coverTitle = $defaultTitle;

    $subtitleText = preg_replace('/\s+/u', ' ', trim((string) ($penawaran->judul ?? '')));
    $coverSubtitle = $subtitleText !== '' ? $subtitleText : '-';
    $subtitleLength = mb_strlen($coverSubtitle);
    $subtitleWords = preg_split('/\s+/u', $coverSubtitle, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $longestSubtitleWord = collect($subtitleWords)->max(fn ($word) => mb_strlen($word)) ?? 0;
    $subtitleScore = $subtitleLength + max(0, $longestSubtitleWord - 18) * 2;
    $subtitleClass = 'cover-subtitle';
    if ($subtitleScore > 200) {
        $subtitleClass .= ' is-maximum';
    } elseif ($subtitleScore > 135) {
        $subtitleClass .= ' is-ultra-long';
    } elseif ($subtitleScore > 90) {
        $subtitleClass .= ' is-extra-long';
    } elseif ($subtitleScore > 45) {
        $subtitleClass .= ' is-long';
    }

    $pillText = preg_replace('/\s+/u', ' ', trim((string) ($penawaran->nama_pekerjaan ?? '')));
    $coverPill = $pillText !== '' ? $pillText : '-';
    $pillLength = mb_strlen($coverPill);
    $pillClass = 'cover-pill';
    if ($pillLength > 130) {
        $pillClass .= ' is-extra-long';
    } elseif ($pillLength > 70) {
        $pillClass .= ' is-long';
    }

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
    $badgeBackground = public_path('templates/badge.png');
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css');

    .cover-page {
        position: relative;
        width: 210mm;
        height: 297mm;
        margin: -10mm;
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

    .cover-badge-bg {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cover-badge-logo-wrap {
        position: absolute;
        inset: 12mm 8mm 10mm 8mm;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cover-badge-logo {
        max-width: 100%;
        max-height: 100%;
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
        font-size: 27pt;
        font-weight: bold;
        line-height: 1.15;
    }

    .cover-subtitle {
        width: 142mm;
        font-size: 24pt;
        font-weight: bold;
        margin-top: 6mm;
        line-height: 1.12;
        letter-spacing: 0;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .cover-subtitle.is-long {
        width: 150mm;
        font-size: 19.5pt;
        line-height: 1.08;
        letter-spacing: -0.05pt;
    }

    .cover-subtitle.is-extra-long {
        width: 154mm;
        font-size: 16.5pt;
        line-height: 1.06;
        letter-spacing: -0.1pt;
    }

    .cover-subtitle.is-ultra-long {
        width: 158mm;
        font-size: 13.5pt;
        line-height: 1.05;
        letter-spacing: -0.1pt;
    }

    .cover-subtitle.is-maximum {
        width: 162mm;
        font-size: 11pt;
        line-height: 1.04;
        letter-spacing: -0.1pt;
    }

    .cover-line {
        width: 16mm;
        height: 1mm;
        border-radius: 10px;
        background: #111;
        margin-top: 3mm;
    }

    .cover-pill {
        position: absolute;
        left: 12mm;
        bottom: 52mm;
        display: inline-block;
        max-width: 176mm;
        text-align: center;
        padding: 3.4mm 9mm;
        border-radius: 999px;
        background: #e3d2a8;
        font-weight: normal;
        font-size: 13pt;
        line-height: 1.2;
        box-sizing: border-box;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .cover-pill.is-long {
        padding: 2.8mm 8mm;
        font-size: 11pt;
        line-height: 1.15;
    }

    .cover-pill.is-extra-long {
        padding: 2.2mm 7mm;
        font-size: 9pt;
        line-height: 1.1;
    }

    .cover-client {
        position: absolute;
        left: 12mm;
        bottom: 40mm;
        width: 176mm;
        font-size: 15pt;
        font-weight: 700;
        text-align: left;
    }

    .cover-footer {
        position: absolute;
        left: 12mm;
        bottom: 24mm;
        width: 186mm;
        text-align: right;
        font-size: 10pt;
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
        <img class="cover-badge-bg" src="{{ $badgeBackground }}" alt="Badge">
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
        <div class="{{ $subtitleClass }}">{{ $coverSubtitle }}</div>
        <div class="cover-line"></div>
    </div>

    <div class="{{ $pillClass }}">{{ $coverPill }}</div>
    <div class="cover-client">{{ $coverClient }}</div>

    <div class="cover-footer">
        <span class="footer-item"><span class="footer-icon"><img src="{{ public_path('templates/email.png') }}"
                    alt="" class="footer-icon-img"></span><span
                class="footer-text">{{ $kop['email'] ?? $cover?->perusahaan_email ?? 'cv.artasolusindo@gmail.com' }}</span></span>
        <span class="footer-item"><span class="footer-icon"><img src="{{ public_path('templates/wa.png') }}"
                    alt="" class="footer-icon-img"></span><span
                class="footer-text">{{ $kop['telp'] ?? $cover?->perusahaan_telp ?? '085727868505' }}</span></span>
    </div>
    <div class="ornamen-footer" style="position: absolute; left: 0; bottom: 0; width: 210mm;">
        <img src="{{ public_path('templates/ornamen_bawah.png') }}"
            style="width: 210mm; height: auto; object-fit: contain; display: block;" alt="Logo">
    </div>
</div>
