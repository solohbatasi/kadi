<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_audit_logs_and_normal_user_cannot(): void
    {
        $admin = $this->adminUser();
        $normal = User::factory()->withPersonalTeam()->create();
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'merchant.activated',
            'metadata' => ['secret_key' => 'hidden'],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk()->assertSee('merchant.activated')->assertDontSee('hidden');
        $this->actingAs($normal)->get('/admin/audit-logs')->assertForbidden();
    }

    protected function adminUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->roles()->attach($role);

        return $user;
    }
}

