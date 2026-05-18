<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'role']);

        $users = User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->whereHas('roles', fn ($query) => $query->where('roles.id', $role)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'permissions' => Permission::orderBy('name')->get(['id', 'name', 'group']),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $user = User::create(collect($validated)->only(['name', 'email', 'password'])->all());
        $user->roles()->sync($validated['role_ids'] ?? []);
        $user->permissions()->sync($validated['permission_ids'] ?? []);

        return back()->with('flash.banner', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['required', Rule::in(['active', 'suspended', 'terminated'])],
            'status_reason' => ['nullable', 'string', 'max:1000'],
            'suspended_until' => ['nullable', 'date'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $attributes = collect($validated)->except(['role_ids', 'permission_ids'])->all();

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $user->fill($attributes);
        $user->terminated_at = $validated['status'] === 'terminated' ? now() : null;
        $user->save();
        $user->roles()->sync($validated['role_ids'] ?? []);
        $user->permissions()->sync($validated['permission_ids'] ?? []);

        return back()->with('flash.banner', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('flash.banner', 'User deleted.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status_reason' => ['nullable', 'string', 'max:1000'],
            'suspended_until' => ['nullable', 'date'],
        ]);

        $user->forceFill([
            'status' => 'suspended',
            'status_reason' => $validated['status_reason'] ?? 'Suspended by administrator.',
            'suspended_until' => $validated['suspended_until'] ?? null,
            'terminated_at' => null,
        ])->save();

        return back()->with('flash.banner', 'User suspended.');
    }

    public function activate(User $user): RedirectResponse
    {
        $user->forceFill([
            'status' => 'active',
            'status_reason' => null,
            'suspended_until' => null,
            'terminated_at' => null,
        ])->save();

        return back()->with('flash.banner', 'User activated.');
    }

    public function terminate(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'status' => 'terminated',
            'status_reason' => $validated['status_reason'] ?? 'Terminated by administrator.',
            'terminated_at' => now(),
        ])->save();

        return back()->with('flash.banner', 'User terminated.');
    }
}
