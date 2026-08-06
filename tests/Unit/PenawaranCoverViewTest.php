<?php

use App\Models\Penawaran;

uses(Tests\TestCase::class);

function renderPenawaranCoverForTitle(string $title, string $jobName = 'Pengadaan Perangkat'): string
{
    $penawaran = new Penawaran([
        'judul' => $title,
        'nama_pekerjaan' => $jobName,
        'instansi_tujuan' => 'PT Contoh Indonesia',
        'tanggal_penawaran' => '2026-08-06',
    ]);

    return view('documents.partials.penawaran_cover', [
        'penawaran' => $penawaran,
        'cover' => null,
        'kop' => [],
    ])->render();
}

test('cover uses a smaller subtitle for the document 199 title shape', function () {
    $html = renderPenawaranCoverForTitle(
        'SPAREPART ALAT TELEMETRI BEACON ENGINEERING POS BUTUH'
    );

    expect($html)->toContain('class="cover-subtitle is-long"');
});

test('cover subtitle sizing adapts across title lengths', function (string $title, string $expectedClass) {
    $html = renderPenawaranCoverForTitle($title);

    expect($html)->toContain('class="'.$expectedClass.'"');
})->with([
    'short' => ['PENAWARAN PERANGKAT TELEMETRI', 'cover-subtitle'],
    'long' => [str_repeat('PERANGKAT TELEMETRI ', 4), 'cover-subtitle is-long'],
    'extra long' => [str_repeat('PERANGKAT TELEMETRI ', 6), 'cover-subtitle is-extra-long'],
    'ultra long' => [str_repeat('PERANGKAT TELEMETRI ', 8), 'cover-subtitle is-ultra-long'],
    'maximum' => [str_repeat('PERANGKAT TELEMETRI ', 12), 'cover-subtitle is-maximum'],
]);

test('cover shrinks a long job label to preserve the title safe area', function () {
    $html = renderPenawaranCoverForTitle(
        'PENAWARAN PERANGKAT TELEMETRI',
        str_repeat('PENGADAAN DAN PEMASANGAN PERANGKAT ', 4)
    );

    expect($html)->toContain('class="cover-pill is-extra-long"');
});
