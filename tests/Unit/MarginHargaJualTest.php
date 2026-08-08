<?php

use App\Services\Inventory\MarginHargaJual;

/*
 * Rumus margin terhadap harga jual, bukan markup atas modal. Keduanya mudah
 * tertukar dan selisihnya besar, jadi contoh dari tim inventory dipatok di sini.
 */

test('harga jual dihitung dengan margin terhadap harga jual', function () {
    // Contoh resmi: modal 12.530.171,82 dengan margin 30%.
    $jual = MarginHargaJual::hargaJual(12530171.82, 30);

    // Rumusnya menghasilkan 17.900.245,46; dibulatkan ke atas ke kelipatan 1.000.
    expect($jual)->toBe(17901000.0);
});

test('markup atas modal menghasilkan angka yang jauh berbeda', function () {
    $modal = 12530171.82;

    $benar = MarginHargaJual::hargaJual($modal, 30);
    $markup = $modal * 1.3;

    // Markup 30% hanya memberi margin sekitar 23% -- tujuh poin di bawah target.
    expect(round(MarginHargaJual::margin($modal, $markup), 0))->toBe(23.0)
        ->and($benar)->toBeGreaterThan($markup)
        ->and(round($markup))->toBe(16289223.0);
});

test('margin yang benar-benar didapat dihitung dari harga jual', function () {
    expect(round(MarginHargaJual::margin(70, 100), 6))->toBe(30.0)
        ->and(round(MarginHargaJual::margin(12530171.82, 17901000.0), 1))->toBe(30.0);
});

test('pembulatan selalu ke atas, tidak pernah ke terdekat', function (float $nilai, float $harapan) {
    // Ke bawah akan menurunkan margin ke bawah target tanpa terlihat.
    expect(MarginHargaJual::bulatkanKeAtas($nilai))->toBe($harapan);
})->with([
    'tepat di kelipatan' => [17901000.0, 17901000.0],
    'sedikit di atas' => [17900001.0, 17901000.0],
    'hampir kelipatan berikutnya' => [17900999.0, 17901000.0],
    'jauh di bawah tengah' => [17900245.46, 17901000.0],
]);

test('hasil yang tepat di kelipatan tidak dinaikkan oleh sisa pembagian biner', function () {
    // 700.000 / 0,7 dihitung komputer sebagai 1.000.000,0000000001. Tanpa
    // pembersihan, pembulatan ke atas menaikkannya jadi Rp 1.001.000 tanpa sebab.
    expect(MarginHargaJual::hargaJual(700000.0, 30))->toBe(1000000.0)
        ->and(MarginHargaJual::hargaJual(350000.0, 30))->toBe(500000.0)
        ->and(MarginHargaJual::hargaJual(500000.0, 50))->toBe(1000000.0);
});

test('pembulatan ke atas membuat margin tidak pernah di bawah target', function (float $modal, float $target) {
    $jual = MarginHargaJual::hargaJual($modal, $target);

    expect(MarginHargaJual::margin($modal, $jual))->toBeGreaterThanOrEqual($target);
})->with([
    'nilai besar' => [12530171.82, 30.0],
    'nilai kecil' => [5000.0, 30.0],
    'margin tipis' => [250000.0, 5.0],
    'margin tebal' => [250000.0, 60.0],
]);

test('margin di luar batas tidak menghasilkan harga jual', function (?float $margin) {
    // 100% berarti pembagian dengan nol; di atasnya harga jual jadi negatif.
    expect(MarginHargaJual::hargaJual(1000000.0, $margin))->toBeNull();
})->with([
    'negatif' => [-1.0],
    'seratus persen' => [100.0],
    'di atas seratus' => [140.0],
    'tidak diisi' => [null],
]);

test('modal yang tidak ada tidak menghasilkan angka apa pun', function () {
    expect(MarginHargaJual::hargaJual(null, 30))->toBeNull()
        ->and(MarginHargaJual::hargaJual(0.0, 30))->toBeNull()
        ->and(MarginHargaJual::margin(null, 100.0))->toBeNull()
        ->and(MarginHargaJual::margin(70.0, null))->toBeNull()
        ->and(MarginHargaJual::margin(70.0, 0.0))->toBeNull();
});

test('margin nol berarti harga jual sama dengan modal', function () {
    // Dibulatkan ke atas, jadi bisa sedikit di atas modal -- bukan di bawahnya.
    expect(MarginHargaJual::hargaJual(1000000.0, 0))->toBe(1000000.0)
        ->and(MarginHargaJual::hargaJual(1000001.0, 0))->toBe(1001000.0);
});
