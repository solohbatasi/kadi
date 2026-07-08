<?php

namespace App\Services\Payments;

use App\Models\Merchant;
use App\Models\MerchantProfile;
use App\Models\MerchantWebhookEndpoint;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Str;

class MerchantBootstrapService
{
    public function bootstrapForUser(User $user): Merchant
    {
        return Merchant::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'public_id' => $this->generatePublicId(),
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => false,
        ], function (Merchant $merchant) use ($user) {
            $merchant->business_name = $user->name;
            $merchant->business_email = $user->email;
            $merchant->save();
        });
    }

    public function ensureProfile(Merchant $merchant): MerchantProfile
    {
        return MerchantProfile::firstOrCreate([
            'merchant_id' => $merchant->id,
        ], [
            'notification_email_enabled' => true,
            'notification_sms_enabled' => false,
        ]);
    }

    public function ensureWallet(Merchant $merchant): Wallet
    {
        return Wallet::firstOrCreate([
            'merchant_id' => $merchant->id,
        ], [
            'public_id' => $this->generateWalletPublicId(),
            'available_balance' => 0,
            'pending_balance' => 0,
            'currency' => config('payments.currency', 'KES'),
        ]);
    }

    protected function generateWalletPublicId(): string
    {
        return 'wal_'.Str::random(24);
    }

    public function ensureWebhookEndpoint(Merchant $merchant): MerchantWebhookEndpoint
    {
        return MerchantWebhookEndpoint::firstOrCreate([
            'merchant_id' => $merchant->id,
        ], [
            'is_enabled' => false,
        ]);
    }

    protected function generatePublicId(): string
    {
        return 'mer_'.Str::random(24);
    }
}
