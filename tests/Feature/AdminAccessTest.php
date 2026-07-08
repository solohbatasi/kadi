<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_normal_merchant_cannot_access_admin(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_non_admin_gets_403(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)->get('/admin/merchants')->assertForbidden();
    }

    protected function adminUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->roles()->attach($role);

        return $user;
    }
}

