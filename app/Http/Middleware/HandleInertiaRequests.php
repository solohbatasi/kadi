<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $merchant = $user?->merchant;

        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'status' => $user->status,
                ] : null,
                'roles' => fn () => $user
                    ? $user->roles()->orderBy('name')->pluck('name')->values()
                    : [],
                'is_admin' => fn () => $user
                    ? $user->loadMissing('roles')->hasRole('admin')
                    : false,
                'merchant' => fn () => $merchant ? [
                    'public_id' => $merchant->public_id,
                    'business_name' => $merchant->business_name,
                    'business_email' => $merchant->business_email,
                    'business_phone' => $merchant->business_phone,
                    'status' => $merchant->status,
                    'compliance_status' => $merchant->compliance_status,
                    'live_enabled' => $merchant->live_enabled,
                ] : null,
                'merchant_status' => fn () => $merchant?->status,
                'compliance_status' => fn () => $merchant?->compliance_status,
                'live_enabled' => fn () => (bool) ($merchant?->live_enabled ?? false),
            ],
        ];
    }
}
