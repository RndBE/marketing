<?php

test('authenticated layout exposes validation error keys for the frontend focus helper', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)
        ->toContain('data-validation-error-summary')
        ->toContain('data-validation-error-keys')
        ->toContain('$errors->keys()');
});

test('frontend scrolls and focuses the first validation error field', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('window.validationErrorFocus')
        ->toContain('focusFirstErrorField')
        ->toContain('data-validation-error-keys')
        ->toContain('scrollIntoView')
        ->toContain('focus({ preventScroll: true })')
        ->toContain('data-validation-error-highlighted');
});

test('frontend can resolve laravel dot notation validation keys to bracket input names', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('errorKeyToFieldNames')
        ->toContain('segments[0] + segments.slice(1).map((segment) => `[${segment}]`).join(\'\')')
        ->toContain('querySelector');
});
