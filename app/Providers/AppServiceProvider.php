<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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

            if (!$user) {
                return;
            }

            $companies = $user->hasRole('admin')
                ? Company::query()->orderBy('name')->get(['id', 'name', 'code'])
                : collect();

            $activeCompanyId = null;
            $activeCompany = null;

            if ($user->hasRole('admin')) {
                $selectedCompanyId = (int) session('active_company_id', 0);
                $activeCompany = $companies->firstWhere('id', $selectedCompanyId);

                if (!$activeCompany && $user->company_id) {
                    $activeCompany = $companies->firstWhere('id', (int) $user->company_id);
                }

                if (!$activeCompany) {
                    $activeCompany = $companies->first();
                }

                $activeCompanyId = $activeCompany?->id;
            } else {
                $activeCompanyId = $user->company_id;
                $activeCompany = $user->company;
            }

            $view->with([
                'layoutAvailableCompanies' => $companies,
                'layoutActiveCompanyId' => $activeCompanyId,
                'layoutActiveCompany' => $activeCompany,
            ]);
        });
    }
}
