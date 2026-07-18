<?php

test('application uses WIB and Indonesian locale defaults', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(config('app.locale'))->toBe('id')
        ->and(config('app.fallback_locale'))->toBe('en')
        ->and(config('app.faker_locale'))->toBe('id_ID');
});
