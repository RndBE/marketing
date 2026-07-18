<?php

test('global javascript provides loading states for search and filter forms', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('window.filterFormLoading')
        ->toContain('setFilterFormLoading')
        ->toContain('isFilterForm')
        ->toContain("form.method.toUpperCase() === 'GET'")
        ->toContain('data-no-filter-loading')
        ->toContain('aria-busy')
        ->toContain('Memuat...')
        ->toContain('data-filter-loading-original-html')
        ->toContain("window.addEventListener('pageshow'");
});

test('penawaran filter forms submit through the loading-aware submit flow', function () {
    $index = file_get_contents(resource_path('views/penawaran/index.blade.php'));
    $fields = file_get_contents(resource_path('views/penawaran/partials/filter-fields.blade.php'));

    expect($index)
        ->toContain('data-penawaran-filter-form')
        ->toContain('form.requestSubmit()')
        ->not->toContain('form.submit()');

    expect($fields)
        ->toContain('this.form.requestSubmit()')
        ->not->toContain('this.form.submit()');
});

test('common index search forms can use the global loading behavior without local scripts', function () {
    foreach ([
        'invoices/index.blade.php',
        'prospects/index.blade.php',
        'price_list/index.blade.php',
        'companies/index.blade.php',
        'users/index.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect($contents)
            ->toContain('method="GET"')
            ->not->toContain('data-no-filter-loading');
    }
});

