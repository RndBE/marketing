<?php

test('authenticated layout exposes one accessible global toast region', function () {
    expect(view()->exists('components.toast-notifications'))->toBeTrue();

    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $component = file_get_contents(resource_path('views/components/toast-notifications.blade.php'));

    expect($layout)
        ->toContain('<x-toast-notifications />')
        ->not->toContain("@if (session('success'))");
    expect($component)
        ->toContain('data-global-toast-notifications')
        ->toContain('aria-live="polite"')
        ->toContain(":role=\"toast.type === 'error' ? 'alert' : 'status'\"")
        ->toContain('@show-toast.window')
        ->toContain('bg-emerald-50')
        ->toContain('bg-rose-50');
});

test('global toast javascript supports redirect and ajax notifications consistently', function () {
    $component = file_get_contents(resource_path('views/components/toast-notifications.blade.php'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($component)
        ->toContain("session('success')")
        ->toContain("session('error')");
    expect($javascript)
        ->toContain("Alpine.data('toastNotifications'")
        ->toContain('window.showToast')
        ->toContain("new CustomEvent('show-toast'")
        ->toContain('this.toasts.length >= 3');
});

test('page specific flash banners are removed to prevent duplicate notifications', function () {
    foreach ([
        'roles/index.blade.php',
        'usulan/show.blade.php',
        'usulan/index.blade.php',
        'users/index.blade.php',
        'user-roles/index.blade.php',
        'permissions/index.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect($contents)
            ->not->toContain("session('success')")
            ->not->toContain("session('error')");
    }
});

test('profile status messages are normalized into the same global toast', function () {
    $component = file_get_contents(resource_path('views/components/toast-notifications.blade.php'));

    expect($component)
        ->toContain("'profile-updated'")
        ->toContain("'password-updated'")
        ->toContain("'verification-link-sent'");

    foreach ([
        'profile/partials/update-profile-information-form.blade.php',
        'profile/partials/update-password-form.blade.php',
    ] as $view) {
        expect(file_get_contents(resource_path('views/'.$view)))
            ->not->toContain("session('status')");
    }
});

test('penawaran ajax feedback uses the global toast helper', function () {
    $contents = file_get_contents(resource_path('views/penawaran/show.blade.php'));

    expect($contents)
        ->toContain("showToast(data?.message || 'Berhasil!', 'success')")
        ->not->toContain('function showToast(');
});
