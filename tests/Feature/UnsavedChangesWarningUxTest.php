<?php

test('authenticated layout exposes a reusable unsaved changes dialog', function () {
    expect(view()->exists('components.unsaved-changes-confirmation'))->toBeTrue();

    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $component = file_get_contents(resource_path('views/components/unsaved-changes-confirmation.blade.php'));

    expect($layout)->toContain('<x-unsaved-changes-confirmation />');
    expect($component)
        ->toContain('data-global-unsaved-confirmation')
        ->toContain('Perubahan belum disimpan')
        ->toContain('Keluar tanpa menyimpan')
        ->toContain('Tetap di halaman')
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"');
});

test('global javascript tracks dirty editable forms and browser unloads', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("Alpine.data('unsavedChangesConfirmation'")
        ->toContain('window.requestUnsavedChangesConfirmation')
        ->toContain('window.unsavedChanges')
        ->toContain("window.addEventListener('beforeunload'")
        ->toContain('getFormSignature')
        ->toContain('hasDirtyForms')
        ->toContain('markFormClean');
});

test('unsaved tracker ignores action only forms and filter forms', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("form.method.toUpperCase() === 'GET'")
        ->toContain('data-no-unsaved-warning')
        ->toContain('data-confirm-delete')
        ->toContain('data-confirm-duplicate')
        ->toContain('data-duplicate-submit')
        ->toContain("input:not([type='hidden'])");
});

test('app navigation and non save submissions ask before discarding dirty forms', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("document.addEventListener('click'")
        ->toContain("target.closest('a[href]')")
        ->toContain("document.addEventListener('submit'")
        ->toContain('requestUnsavedChangesConfirmation')
        ->toContain('unsavedConfirmed');
});

