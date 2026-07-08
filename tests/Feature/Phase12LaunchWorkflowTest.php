<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase12LaunchWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected array $envKeys = [
        'INITIAL_ADMIN_NAME',
        'INITIAL_ADMIN_EMAIL',
        'INITIAL_ADMIN_PASSWORD',
    ];

    protected function tearDown(): void
    {
        foreach ($this->envKeys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        parent::tearDown();
    }

    public function test_admin_user_seeder_creates_admin_from_environment(): void
    {
        $this->setEnv('INITIAL_ADMIN_NAME', 'Launch Admin');
        $this->setEnv('INITIAL_ADMIN_EMAIL', 'launch-admin@example.test');
        $this->setEnv('INITIAL_ADMIN_PASSWORD', 'password-secret');

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'launch-admin@example.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_admin_user_seeder_does_not_duplicate_admin(): void
    {
        $this->setEnv('INITIAL_ADMIN_NAME', 'Launch Admin');
        $this->setEnv('INITIAL_ADMIN_EMAIL', 'launch-admin@example.test');
        $this->setEnv('INITIAL_ADMIN_PASSWORD', 'password-secret');

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'launch-admin@example.test')->count());
    }

    public function test_admin_user_seeder_skips_safely_when_environment_missing(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count());
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_prelive_check_command_runs_without_printing_secrets_in_local_safe_mode(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.debug' => false,
            'app.url' => 'https://example.test',
            'mpesa.callback_secret' => 'super-secret-callback',
            'mpesa.callback_url' => 'https://example.test/api/mpesa/stk-callback/secret',
            'queue.default' => 'database',
            'operations.alert_email' => 'ops@example.test',
        ]);
        $this->createAdmin();

        $this->artisan('payments:prelive-check')
            ->expectsOutputToContain('APPLICATION')
            ->doesntExpectOutputToContain('super-secret-callback')
            ->assertExitCode(0);
    }

    public function test_prelive_check_fails_in_production_like_missing_config(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'app.key' => '',
            'app.debug' => true,
            'app.url' => 'http://example.test',
            'mpesa.callback_secret' => '',
            'queue.default' => 'sync',
        ]);

        $this->artisan('payments:prelive-check')
            ->expectsOutputToContain('[FAIL]')
            ->assertExitCode(1);
    }

    public function test_required_documentation_files_exist(): void
    {
        foreach ([
            'docs/DEPLOYMENT.md',
            'docs/PRODUCTION_READINESS.md',
            'docs/RUNBOOK.md',
            'docs/BACKUP_RESTORE.md',
            'docs/supervisor/payment-gateway-worker.conf.example',
            'docs/systemd/payment-gateway-worker.service.example',
        ] as $path) {
            $this->assertFileExists(base_path($path));
        }
    }

    public function test_admin_prelive_page_is_admin_only(): void
    {
        $admin = $this->createAdmin();
        $normal = User::factory()->withPersonalTeam()->create();

        $this->actingAs($admin)
            ->get(route('admin.prelive-check.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/PreLiveCheck/Index'));

        $this->actingAs($normal)
            ->get(route('admin.prelive-check.index'))
            ->assertForbidden();
    }

    protected function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    protected function createAdmin(): User
    {
        $admin = User::factory()->withPersonalTeam()->create();
        $role = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        $admin->roles()->syncWithoutDetaching([$role->id]);

        return $admin;
    }
}

