<?php

use App\Models\PenawaranItemDetail;

test('unit price includes the detail markup', function () {
    $detail = new PenawaranItemDetail([
        'qty' => 2,
        'harga' => 700000,
        'subtotal' => 1680000,
        'markup' => 1.2,
    ]);

    expect($detail->calcUnitPrice())->toBe(840000)
        ->and($detail->calcSubtotal())->toBe(1680000);
});

test('unit price is derived from harga when the subtotal is not stored', function () {
    $detail = new PenawaranItemDetail([
        'qty' => 2,
        'harga' => 700000,
        'subtotal' => 0,
        'markup' => 1.2,
    ]);

    expect($detail->calcUnitPrice())->toBe(840000);
});

test('unit price falls back to harga times markup when qty is zero', function () {
    $detail = new PenawaranItemDetail([
        'qty' => 0,
        'harga' => 700000,
        'subtotal' => 0,
        'markup' => 1.2,
    ]);

    expect($detail->calcUnitPrice())->toBe(840000);
});

test('unit price equals harga when there is no markup', function () {
    $detail = new PenawaranItemDetail([
        'qty' => 2,
        'harga' => 700000,
        'subtotal' => 1400000,
        'markup' => 1,
    ]);

    expect($detail->calcUnitPrice())->toBe(700000);
});
