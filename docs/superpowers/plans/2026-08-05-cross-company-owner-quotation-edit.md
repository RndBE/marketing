# Cross-Company Owner Quotation Edit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a quotation's original owner continue editing it after moving companies while keeping cross-company deletion and non-owner editing blocked.

**Architecture:** Extend the existing centralized content-edit guard with an owner bypass, mirroring the moved-owner view rule. Move whole-document deletion and deletion-request endpoints to a separate strict guard that preserves the original same-company requirement. Route permission middleware remains the first authorization layer.

**Tech Stack:** PHP 8.2+, Laravel 12, Pest 3, SQLite in-memory feature tests

---

### Task 1: Add moved-owner authorization regression coverage

**Files:**
- Create: `tests/Feature/PenawaranCrossCompanyOwnerEditTest.php`
- Reference: `tests/Feature/PenawaranIndexFilterTest.php:149-170`
- Reference: `routes/web.php:74-109`

- [ ] **Step 1: Write the feature tests**

Create `tests/Feature/PenawaranCrossCompanyOwnerEditTest.php` with focused helpers and HTTP-level authorization assertions:

```php
<?php

use App\Models\Company;
use App\Models\Penawaran;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function crossCompanyEditUser(Company $company, array $permissionSlugs): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Cross Company Edit Tester '.uniqid(),
        'slug' => 'cross-company-edit-tester-'.uniqid(),
    ]);

    foreach ($permissionSlugs as $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'group' => 'Penawaran']
        );
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

function crossCompanyEditOffer(Company $company, User $owner, string $title): Penawaran
{
    return Penawaran::create([
        'company_id' => $company->id,
        'id_user' => $owner->id,
        'judul' => $title,
        'date_created' => now()->timestamp,
        'date_updated' => now()->timestamp,
    ]);
}

test('moved owner with edit permission can edit and update their old quotation', function () {
    $cv = Company::create(['code' => 'OWNER-CV', 'name' => 'Owner CV']);
    $pt = Company::create(['code' => 'OWNER-PT', 'name' => 'Owner PT']);
    $owner = crossCompanyEditUser($cv, ['edit-penawaran']);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran CV Sebelum Pindah');

    $owner->update(['company_id' => $pt->id]);

    $this->actingAs($owner)
        ->get(route('penawaran.edit', $offer))
        ->assertOk();

    $this->actingAs($owner)
        ->put(route('penawaran.update', $offer), [
            'judul' => 'Penawaran CV Setelah Diedit Pemilik',
            'catatan' => 'Diedit setelah pemilik pindah ke PT.',
        ])
        ->assertRedirect(route('penawaran.show', $offer));

    $this->assertDatabaseHas('penawaran', [
        'id' => $offer->id,
        'company_id' => $cv->id,
        'id_user' => $owner->id,
        'judul' => 'Penawaran CV Setelah Diedit Pemilik',
        'catatan' => 'Diedit setelah pemilik pindah ke PT.',
    ]);
});

test('moved owner without edit permission remains blocked by route middleware', function () {
    $cv = Company::create(['code' => 'NO-PERM-CV', 'name' => 'No Permission CV']);
    $pt = Company::create(['code' => 'NO-PERM-PT', 'name' => 'No Permission PT']);
    $owner = crossCompanyEditUser($cv, []);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran Tanpa Permission');

    $owner->update(['company_id' => $pt->id]);

    $this->actingAs($owner)
        ->get(route('penawaran.edit', $offer))
        ->assertForbidden();
});

test('company visibility does not let a non owner edit a cross company quotation', function () {
    $cv = Company::create(['code' => 'SHARED-CV', 'name' => 'Shared CV']);
    $pt = Company::create(['code' => 'SHARED-PT', 'name' => 'Shared PT']);
    $owner = User::factory()->create(['company_id' => $cv->id]);
    $viewer = crossCompanyEditUser($pt, ['edit-penawaran']);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran Dibagikan ke PT');
    $offer->sharedCompanies()->attach($pt->id);

    $this->actingAs($viewer)
        ->get(route('penawaran.edit', $offer))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->put(route('penawaran.update', $offer), ['judul' => 'Tidak Boleh Berubah'])
        ->assertForbidden();

    expect($offer->fresh()->judul)->toBe('Penawaran Dibagikan ke PT');
});

test('moved owner cannot destroy or request deletion of their old quotation', function () {
    $cv = Company::create(['code' => 'DELETE-CV', 'name' => 'Delete CV']);
    $pt = Company::create(['code' => 'DELETE-PT', 'name' => 'Delete PT']);
    $owner = crossCompanyEditUser($cv, ['edit-penawaran', 'delete-penawaran']);
    $offer = crossCompanyEditOffer($cv, $owner, 'Penawaran CV Tidak Boleh Dihapus dari PT');

    $owner->update(['company_id' => $pt->id]);

    $this->actingAs($owner)
        ->delete(route('penawaran.destroy', $offer))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('penawaran.request.delete', $offer))
        ->assertForbidden();

    $this->assertDatabaseHas('penawaran', ['id' => $offer->id]);
});
```

- [ ] **Step 2: Run the focused test and verify RED**

Run:

```bash
php artisan test tests/Feature/PenawaranCrossCompanyOwnerEditTest.php
```

Expected: the moved-owner edit test fails with HTTP 403 instead of 200. The deletion-preservation assertions may already pass because they characterize existing behavior.

- [ ] **Step 3: Commit the failing regression tests**

```bash
git add tests/Feature/PenawaranCrossCompanyOwnerEditTest.php
git commit -m "test: cover moved quotation owner editing"
```

### Task 2: Allow owner editing while preserving destructive boundaries

**Files:**
- Modify: `app/Http/Controllers/PenawaranController.php:421-423`
- Modify: `app/Http/Controllers/PenawaranController.php:1794-1807`
- Modify: `app/Http/Controllers/PenawaranController.php:1947-1949`
- Test: `tests/Feature/PenawaranCrossCompanyOwnerEditTest.php`

- [ ] **Step 1: Route destructive actions through a strict guard**

Change `destroy()` and `requestDelete()` to call a new strict guard:

```php
public function destroy(Penawaran $penawaran)
{
    $this->ensurePenawaranDestructiveAccess($penawaran);

    // Existing method body remains unchanged.
}
```

```php
public function requestDelete(Penawaran $penawaran)
{
    $this->ensurePenawaranDestructiveAccess($penawaran);

    // Existing method body remains unchanged.
}
```

- [ ] **Step 2: Add the owner bypass and strict destructive guard**

Replace the existing edit guard and add the strict guard immediately after it:

```php
private function ensurePenawaranEditAccess(Penawaran $penawaran, $user = null): void
{
    $user ??= auth()->user();

    if ($this->isSuperadmin($user) || $user->hasRole('admin')) {
        return;
    }

    if ((int) $penawaran->id_user === (int) $user->id) {
        return;
    }

    abort(403);
}

private function ensurePenawaranDestructiveAccess(Penawaran $penawaran, $user = null): void
{
    $user ??= auth()->user();

    $this->ensureCompanyAccess($penawaran, 'company_id', $user);

    if ($this->isSuperadmin($user) || $user->hasRole('admin')) {
        return;
    }

    if ((int) $penawaran->id_user !== (int) $user->id) {
        abort(403);
    }
}
```

Permission checks remain in `routes/web.php`; do not duplicate them in the controller.

- [ ] **Step 3: Run the focused test and verify GREEN**

Run:

```bash
php artisan test tests/Feature/PenawaranCrossCompanyOwnerEditTest.php
```

Expected: 4 tests pass with no failures.

- [ ] **Step 4: Run existing penawaran authorization regressions**

Run:

```bash
php artisan test tests/Feature/PenawaranIndexFilterTest.php tests/Feature/PenawaranSignatureTtdTest.php tests/Feature/PenawaranTermTemplateTest.php
```

Expected: all selected tests pass with no failures.

- [ ] **Step 5: Format changed PHP files**

Run:

```bash
vendor/bin/pint app/Http/Controllers/PenawaranController.php tests/Feature/PenawaranCrossCompanyOwnerEditTest.php
```

Expected: Pint exits 0. Re-run the focused test if Pint changes either file.

- [ ] **Step 6: Commit the implementation**

```bash
git add app/Http/Controllers/PenawaranController.php tests/Feature/PenawaranCrossCompanyOwnerEditTest.php
git commit -m "fix: allow moved owners to edit old quotations"
```

### Task 3: Final verification and publication

**Files:**
- Verify: all tracked project files

- [ ] **Step 1: Run the complete test suite**

Run:

```bash
php artisan test
```

Expected: exit 0 with no failed tests.

- [ ] **Step 2: Inspect the final diff and repository state**

Run:

```bash
git status --short
git log -4 --oneline
git diff origin/main...HEAD --check
git diff origin/main...HEAD --stat
```

Expected: the worktree is clean; diff checking reports no whitespace errors; the diff contains only the design, plan, regression test, and permission guard changes.

- [ ] **Step 3: Push the verified commits**

Run:

```bash
git push origin main
```

Expected: `main` advances successfully on `origin` with no non-fast-forward rejection.
