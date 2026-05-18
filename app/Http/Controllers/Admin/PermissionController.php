<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'group']);

        return Inertia::render('Admin/Permissions', [
            'permissions' => Permission::withCount(['roles', 'users'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
                ->when($filters['group'] ?? null, fn ($query, $group) => $query->where('group', $group))
                ->orderBy('group')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'groups' => Permission::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group'),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')],
            'group' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Permission::create($validated);

        return back()->with('flash.banner', 'Permission created.');
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission)],
            'group' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $permission->update($validated);

        return back()->with('flash.banner', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return back()->with('flash.banner', 'Permission deleted.');
    }
}
