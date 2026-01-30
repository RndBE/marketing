<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $groups = Permission::distinct()->pluck('group')->filter()->values();
        return view('permissions.index', compact('permissions', 'groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:permissions,slug',
            'description' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:50',
        ]);

        Permission::create($request->only('name', 'slug', 'description', 'group'));

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dibuat');
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:permissions,slug,' . $permission->id,
            'description' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:50',
        ]);

        $permission->update($request->only('name', 'slug', 'description', 'group'));

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diupdate');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dihapus');
    }
}
