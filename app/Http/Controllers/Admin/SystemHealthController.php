<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class SystemHealthController extends Controller
{
    public function __invoke(): Response
    {
        $sessions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.id', 'sessions.user_id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name', 'users.email')
            ->orderByDesc('sessions.last_activity')
            ->limit(25)
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'user' => $session->name ? "{$session->name} <{$session->email}>" : 'Guest',
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
            ]);

        return Inertia::render('Admin/SystemHealth', [
            'metrics' => [
                'users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'suspended_users' => User::where('status', 'suspended')->count(),
                'terminated_users' => User::where('status', 'terminated')->count(),
                'roles' => Role::count(),
                'permissions' => Permission::count(),
                'api_tokens' => PersonalAccessToken::count(),
                'active_sessions' => DB::table('sessions')->count(),
            ],
            'security' => [
                ['name' => 'Password hashing', 'status' => 'Enforced', 'detail' => 'User passwords are cast through Laravel hashing.'],
                ['name' => 'Two-factor authentication', 'status' => 'Available', 'detail' => 'Jetstream two-factor columns and profile controls are enabled.'],
                ['name' => 'Browser sessions', 'status' => config('session.driver') === 'database' ? 'Tracked' : 'Driver: '.config('session.driver'), 'detail' => 'Frontend access is session based with CSRF protection and SameSite cookies.'],
                ['name' => 'Mobile API tokens', 'status' => 'Enabled', 'detail' => 'Sanctum personal access tokens support mobile and external clients.'],
                ['name' => 'Suspension enforcement', 'status' => 'Enforced', 'detail' => 'Suspended or terminated users are logged out by web middleware.'],
                ['name' => 'Cookie hardening', 'status' => config('session.http_only') ? 'HttpOnly' : 'Review', 'detail' => 'SameSite: '.config('session.same_site').', Secure cookies: '.(config('session.secure') ? 'yes' : 'env controlled').'.'],
            ],
            'sessions' => $sessions,
            'tokens' => PersonalAccessToken::with('tokenable:id,name,email')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn ($token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'owner' => $token->tokenable?->name ?? 'Unknown',
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->toDateTimeString(),
                    'expires_at' => $token->expires_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function destroySession(string $session): RedirectResponse
    {
        DB::table('sessions')->where('id', $session)->delete();

        return back()->with('flash.banner', 'Session revoked.');
    }

    public function destroyToken(PersonalAccessToken $token): RedirectResponse
    {
        $token->delete();

        return back()->with('flash.banner', 'API token revoked.');
    }
}
