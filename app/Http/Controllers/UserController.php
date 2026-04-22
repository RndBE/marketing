<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with(['roles', 'company'])
            ->when($this->currentCompanyId($request->user()), fn($query, $companyId) => $query->where('company_id', $companyId))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users', 'q'));
    }

    public function create(Request $request)
    {
        $roles = $this->availableRoles($request->user());
        $companies = $this->availableCompanies($request->user());

        return view('users.create', compact('roles', 'companies'));
    }

    public function store(Request $request)
    {
        $actor = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'ttd' => ['nullable', 'image', 'max:2048'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $companyId = $this->isSuperadmin($actor)
            ? ((int) ($validated['company_id'] ?? 0) ?: null)
            : $actor->company_id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['company_id' => 'Perusahaan wajib dipilih.']);
        }

        $roleIds = collect($validated['roles'])->map(fn($id) => (int) $id)->all();
        $this->validateAssignableRoles($roleIds, $actor);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $companyId,
        ]);

        if ($request->hasFile('ttd')) {
            $path = $request->file('ttd')->store('signatures', 'public');
            $user->ttd = $path;
            $user->save();
        }

        $user->roles()->sync($roleIds);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    public function edit(User $user)
    {
        $this->ensureCompanyAccess($user);

        $roles = $this->availableRoles(auth()->user());
        $userRoles = $user->roles->pluck('id')->toArray();
        $companies = $this->availableCompanies(auth()->user());

        return view('users.edit', compact('user', 'roles', 'userRoles', 'companies'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCompanyAccess($user);

        $actor = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'ttd' => ['nullable', 'image', 'max:2048'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $companyId = $this->isSuperadmin($actor)
            ? ((int) ($validated['company_id'] ?? 0) ?: $user->company_id)
            : $user->company_id;

        $roleIds = collect($validated['roles'])->map(fn($id) => (int) $id)->all();
        $this->validateAssignableRoles($roleIds, $actor);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_id' => $companyId,
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('ttd')) {
            if ($user->ttd && Storage::disk('public')->exists($user->ttd)) {
                Storage::disk('public')->delete($user->ttd);
            }
            $user->ttd = $request->file('ttd')->store('signatures', 'public');
        }

        $user->save();
        $user->roles()->sync($roleIds);

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        $this->ensureCompanyAccess($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    private function availableRoles(User $actor)
    {
        $excludedSlugs = $this->isSuperadmin($actor)
            ? ['superadmin']
            : ['admin', 'superadmin'];

        return Role::query()
            ->whereNotIn('slug', $excludedSlugs)
            ->orderBy('name')
            ->get();
    }

    private function availableCompanies(User $actor)
    {
        return Company::query()
            ->when(!$this->isSuperadmin($actor), fn($query) => $query->where('id', $actor->company_id))
            ->orderBy('name')
            ->get();
    }

    private function validateAssignableRoles(array $roleIds, User $actor): void
    {
        $hasLegacySuperadminRole = Role::query()
            ->whereIn('id', $roleIds)
            ->where('slug', 'superadmin')
            ->exists();

        if ($hasLegacySuperadminRole) {
            abort(403, 'Role superadmin tidak lagi dipakai. Gunakan role admin.');
        }

        if ($this->isSuperadmin($actor)) {
            return;
        }

        $hasAdminRole = Role::query()
            ->whereIn('id', $roleIds)
            ->where('slug', 'admin')
            ->exists();

        if ($hasAdminRole) {
            abort(403);
        }
    }
}
