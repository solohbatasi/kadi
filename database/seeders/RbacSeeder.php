<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'users.view', 'group' => 'users', 'description' => 'View user accounts and filters.'],
            ['name' => 'users.create', 'group' => 'users', 'description' => 'Create user accounts.'],
            ['name' => 'users.update', 'group' => 'users', 'description' => 'Update users, roles, and permissions.'],
            ['name' => 'users.suspend', 'group' => 'users', 'description' => 'Suspend, reactivate, or terminate account access.'],
            ['name' => 'roles.manage', 'group' => 'access', 'description' => 'Manage roles and role permissions.'],
            ['name' => 'permissions.manage', 'group' => 'access', 'description' => 'Manage permission definitions.'],
            ['name' => 'health.view', 'group' => 'security', 'description' => 'View system health, sessions, and tokens.'],
            ['name' => 'tokens.revoke', 'group' => 'security', 'description' => 'Revoke API tokens and browser sessions.'],
        ])->map(fn ($permission) => Permission::firstOrCreate(
            ['name' => $permission['name']],
            ['group' => $permission['group'], 'description' => $permission['description']]
        ));

        $administrator = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['guard_name' => 'web', 'description' => 'Full operational access to users, access control, and system health.']
        );

        $support = Role::firstOrCreate(
            ['name' => 'Support'],
            ['guard_name' => 'web', 'description' => 'Frontline account review and support access.']
        );

        $administrator->permissions()->syncWithoutDetaching($permissions->pluck('id'));
        $support->permissions()->syncWithoutDetaching(
            $permissions->whereIn('name', ['users.view', 'health.view'])->pluck('id')
        );

        $firstUser = User::oldest('id')->first();

        if ($firstUser) {
            $firstUser->roles()->syncWithoutDetaching([$administrator->id]);
        }
    }
}
