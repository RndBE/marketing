<?php

test('authenticated layout provides one reusable permanent deletion dialog', function () {
    expect(view()->exists('components.delete-confirmation'))->toBeTrue();

    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $component = file_get_contents(resource_path('views/components/delete-confirmation.blade.php'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($layout)->toContain('<x-delete-confirmation />');
    expect($component)
        ->toContain('data-global-delete-confirmation')
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"');
    expect($javascript)
        ->toContain("Alpine.data('deleteConfirmation'")
        ->toContain('window.requestDeleteConfirmation')
        ->toContain("form.matches('[data-confirm-delete]')");
});

test('permanent deletion dialog uses the application danger theme and clear hierarchy', function () {
    $component = file_get_contents(resource_path('views/components/delete-confirmation.blade.php'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($component)
        ->toContain('data-delete-theme="permanent"')
        ->toContain('Tindakan permanen')
        ->toContain('aria-label="Tutup konfirmasi hapus"')
        ->toContain('bg-rose-50')
        ->toContain('sm:flex-row');

    expect($javascript)
        ->toContain("confirmLabel: 'Hapus Permanen'")
        ->toContain("options.confirmLabel || 'Hapus Permanen'");
});

test('persisted deletion forms opt in to the global confirmation dialog', function () {
    $expectedMarkers = [
        'alurpenawaran/index.blade.php' => 1,
        'companies/index.blade.php' => 1,
        'invoices/index.blade.php' => 2,
        'invoices/show.blade.php' => 4,
        'komponen/index.blade.php' => 2,
        'lead_reports/show.blade.php' => 1,
        'marketing_reports/index.blade.php' => 1,
        'marketing_reports/show.blade.php' => 2,
        'penawaran/partials/actions.blade.php' => 1,
        'penawaran/partials/term_node.blade.php' => 1,
        'penawaran/show.blade.php' => 2,
        'permissions/index.blade.php' => 1,
        'pics/_table.blade.php' => 1,
        'price_list/index.blade.php' => 1,
        'price_list/partials/details_table.blade.php' => 1,
        'price_list/show.blade.php' => 1,
        'prospects/index.blade.php' => 1,
        'prospects/show.blade.php' => 1,
        'roles/index.blade.php' => 1,
        'templates/index.blade.php' => 2,
        'term_templates/partials/node.blade.php' => 1,
        'users/index.blade.php' => 1,
        'usulan/index.blade.php' => 1,
        'usulan/show.blade.php' => 1,
    ];

    foreach ($expectedMarkers as $view => $minimumCount) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect(substr_count($contents, 'data-confirm-delete'))->toBeGreaterThanOrEqual($minimumCount);
    }
});

test('javascript deletions use the global dialog and legacy delete confirms are gone', function () {
    foreach ([
        'invoices/show.blade.php',
        'penawaran/show.blade.php',
        'usulan/edit.blade.php',
    ] as $view) {
        expect(file_get_contents(resource_path('views/'.$view)))
            ->toContain('window.requestDeleteConfirmation');
    }

    $unexpectedNativeConfirmations = [];

    $views = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($views as $view) {
        if (! $view->isFile() || ! str_ends_with($view->getFilename(), '.blade.php')) {
            continue;
        }

        foreach (file($view->getPathname()) as $lineNumber => $line) {
            if (! preg_match('/(?:return\s+|!\s*|window\.)confirm\(/', $line)) {
                continue;
            }

            $unexpectedNativeConfirmations[] = $view->getFilename().':'.($lineNumber + 1).': '.trim($line);
        }
    }

    expect($unexpectedNativeConfirmations)->toBe([]);
});

test('temporary removals and account password confirmation keep their existing behavior', function () {
    $usulanCreate = file_get_contents(resource_path('views/usulan/create.blade.php'));
    $accountDeletion = file_get_contents(resource_path('views/profile/partials/delete-user-form.blade.php'));

    expect($usulanCreate)
        ->toContain("this.closest('.item-row').remove()")
        ->not->toContain('data-confirm-delete');
    expect($accountDeletion)
        ->toContain('password')
        ->toContain('data-delete-theme="permanent-password"')
        ->toContain('Tindakan permanen')
        ->toContain('Hapus Permanen')
        ->toContain('<x-modal');
});
