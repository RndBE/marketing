<?php

use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;

test('loaded zero-valued details override a stale item subtotal', function () {
    $item = new PenawaranItem([
        'qty' => 1,
        'subtotal' => 750000,
        'markup' => 1,
    ]);

    $item->setRelation('details', collect([
        new PenawaranItemDetail([
            'qty' => 1,
            'harga' => 0,
            'subtotal' => 0,
            'markup' => 1,
        ]),
    ]));

    expect($item->calcBaseUnitSubtotal())->toBe(0)
        ->and($item->calcUnitSubtotal())->toBe(0)
        ->and($item->calcSubtotal())->toBe(0);
});

test('stored subtotal remains the fallback when details are not loaded', function () {
    $item = new PenawaranItem([
        'qty' => 1,
        'subtotal' => 750000,
        'markup' => 1,
    ]);

    expect($item->calcBaseUnitSubtotal())->toBe(750000);
});
