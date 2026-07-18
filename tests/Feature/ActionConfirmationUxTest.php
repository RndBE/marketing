<?php

test('authenticated layout provides one reusable non permanent action confirmation dialog', function () {
    expect(view()->exists('components.action-confirmation'))->toBeTrue();

    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $component = file_get_contents(resource_path('views/components/action-confirmation.blade.php'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($layout)->toContain('<x-action-confirmation />');
    expect($component)
        ->toContain('data-global-action-confirmation')
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('Konfirmasi aksi')
        ->toContain('bg-amber-50')
        ->toContain('text-amber-700');
    expect($javascript)
        ->toContain("Alpine.data('actionConfirmation'")
        ->toContain('window.requestActionConfirmation')
        ->toContain("form.matches('[data-confirm-action]')");
});

test('non permanent action forms opt in to the reusable action confirmation dialog', function () {
    $expectedMarkers = [
        'penawaran/show.blade.php' => [
            'data-confirm-action',
            'Cabut Status Goal / Project?',
            'Tandai Sebagai Goal / Project?',
        ],
        'penawaran/partials/actions.blade.php' => [
            'data-confirm-action',
            'Ajukan Penghapusan Penawaran?',
        ],
        'prospects/show.blade.php' => [
            'data-confirm-action',
            'Lepas Usulan dari Prospek?',
            'Lepas Penawaran dari Prospek?',
        ],
    ];

    foreach ($expectedMarkers as $view => $markers) {
        $contents = file_get_contents(resource_path('views/'.$view));

        foreach ($markers as $marker) {
            expect($contents)->toContain($marker);
        }
    }
});

test('non permanent action forms no longer use native browser confirmation', function () {
    $unexpectedNativeConfirmations = [];
    $views = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($views as $view) {
        if (! $view->isFile() || ! str_ends_with($view->getFilename(), '.blade.php')) {
            continue;
        }

        foreach (file($view->getPathname()) as $lineNumber => $line) {
            if (preg_match('/(?:return\s+|!\s*|window\.)confirm\(/', $line)) {
                $unexpectedNativeConfirmations[] = $view->getFilename().':'.($lineNumber + 1).': '.trim($line);
            }
        }
    }

    expect($unexpectedNativeConfirmations)->toBe([]);
});
