<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    protected function authUser(): ?User
    {
        return auth()->user();
    }

    protected function isSuperadmin(?User $user = null): bool
    {
        $user ??= $this->authUser();

        return $user?->hasRole('admin') ?? false;
    }

    protected function currentCompanyId(?User $user = null): ?int
    {
        $user ??= $this->authUser();

        if (!$user) {
            return null;
        }

        if (!$this->isSuperadmin($user)) {
            return $user->company_id;
        }

        $selectedCompanyId = (int) session('active_company_id', 0);
        if ($selectedCompanyId > 0 && Company::query()->whereKey($selectedCompanyId)->exists()) {
            return $selectedCompanyId;
        }

        if ($user->company_id && Company::query()->whereKey($user->company_id)->exists()) {
            return (int) $user->company_id;
        }

        return Company::query()->orderBy('name')->value('id');
    }

    protected function currentCompany(?User $user = null): ?Company
    {
        $companyId = $this->currentCompanyId($user);

        return $companyId ? Company::query()->find($companyId) : null;
    }

    protected function scopeToCompany(
        Builder $query,
        ?User $user = null,
        string $column = 'company_id',
        ?int $companyId = null
    ): Builder {
        $user ??= $this->authUser();

        if (!$user) {
            return $query;
        }

        $resolvedCompanyId = $companyId ?? $this->currentCompanyId($user);

        if ($resolvedCompanyId === null) {
            return $query;
        }

        return $query->where($column, $resolvedCompanyId);
    }

    protected function ensureCompanyIdAccess(?int $companyId, ?User $user = null): void
    {
        $user ??= $this->authUser();

        if (!$user || $this->isSuperadmin($user)) {
            return;
        }

        if ((int) $companyId !== (int) $this->currentCompanyId($user)) {
            abort(403);
        }
    }

    protected function ensureCompanyAccess(Model $model, string $column = 'company_id', ?User $user = null): void
    {
        $this->ensureCompanyIdAccess((int) data_get($model, $column), $user);
    }

    protected function companyUsersQuery(?int $companyId = null): Builder
    {
        $companyId ??= $this->currentCompanyId();

        return User::query()
            ->when($companyId !== null, fn($query) => $query->where('company_id', $companyId));
    }

    protected function ensureUserBelongsToCompany(?int $userId, ?int $companyId = null): ?User
    {
        if (!$userId) {
            return null;
        }

        $query = User::query()->whereKey($userId);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    protected function resolveCompanyUser(int $companyId, ?int $preferredUserId = null): User
    {
        $actor = $this->authUser();

        if ($actor && (int) $actor->company_id === $companyId) {
            return $actor;
        }

        if ($preferredUserId) {
            $preferredUser = User::query()
                ->whereKey($preferredUserId)
                ->where('company_id', $companyId)
                ->first();

            if ($preferredUser) {
                return $preferredUser;
            }
        }

        return User::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->firstOrFail();
    }
}
