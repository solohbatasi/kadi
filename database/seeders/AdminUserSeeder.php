<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('INITIAL_ADMIN_NAME');
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web', 'description' => 'Platform owner operations access.']
        );

        if (! $name || ! $email || ! $password) {
            $this->command?->warn('INITIAL_ADMIN_* values are missing. Admin user bootstrap skipped.');
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->command?->info("Admin user ready: {$email}");
    }
}

