<?php

namespace App\Providers;

use App\Models\Company;
use App\Services\PerusahaanAktif;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $companies = $user->hasRole('admin')
                ? Company::query()->orderBy('name')->get(['id', 'name', 'code'])
                : collect();

            // Aturannya tinggal di satu kelas, supaya pemeriksaan hak di luar
            // tampilan memakai jawaban yang sama persis.
            $activeCompany = PerusahaanAktif::untuk($user);
            $activeCompanyId = $activeCompany?->id;

            $view->with([
                'layoutAvailableCompanies' => $companies,
                'layoutActiveCompanyId' => $activeCompanyId,
                'layoutActiveCompany' => $activeCompany,
            ]);
        });
    }
}
