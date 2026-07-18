<?php

test('all duplicate actions expose one consistent loading state', function () {
    foreach ([
        'penawaran/show.blade.php',
        'penawaran/partials/actions.blade.php',
        'price_list/show.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect($contents)
            ->toContain('data-duplicate-submit')
            ->toContain('data-confirm-duplicate')
            ->toContain('data-duplicate-button')
            ->toContain('data-duplicate-spinner')
            ->toContain('data-loading-label="Menduplikat..."')
            ->not->toContain("onsubmit=\"return confirm('Duplikat");
    }
});

test('duplicate confirmation uses one application themed dialog instead of browser confirm', function () {
    expect(view()->exists('components.duplicate-confirmation'))->toBeTrue();

    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $component = file_get_contents(resource_path('views/components/duplicate-confirmation.blade.php'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($layout)->toContain('<x-duplicate-confirmation />');
    expect($component)
        ->toContain('data-global-duplicate-confirmation')
        ->toContain('role="dialog"')
        ->toContain('Konfirmasi duplikat')
        ->toContain('bg-indigo-50');
    expect($javascript)
        ->toContain("Alpine.data('duplicateConfirmation'")
        ->toContain('window.requestDuplicateConfirmation')
        ->toContain("form.matches('[data-confirm-duplicate]')");
});

test('duplicate dialog stays visible and shows progress while the server creates the copy', function () {
    $component = file_get_contents(resource_path('views/components/duplicate-confirmation.blade.php'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($component)
        ->toContain('data-duplicate-modal-progress')
        ->toContain('x-show="processing"')
        ->toContain('Menduplikat...')
        ->toContain(':disabled="processing"');

    expect($javascript)
        ->toContain('processing: false')
        ->toContain('this.processing = true;')
        ->toContain('if (this.processing)')
        ->toContain("this.processing = true;\n        const callback = this.onConfirm;");
});

test('duplicate submit handler prevents repeated requests and restores cached pages', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('setDuplicateSubmitState')
        ->toContain("form.matches('[data-duplicate-submit]')")
        ->toContain('event.defaultPrevented')
        ->toContain("form.dataset.duplicateSubmitting === 'true'")
        ->toContain("button.setAttribute('aria-busy'")
        ->toContain("window.addEventListener('pageshow'");
});
