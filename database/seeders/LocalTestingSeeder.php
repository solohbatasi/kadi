<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Merchant;
use App\Models\MerchantProfile;
use App\Models\MerchantWebhookEndpoint;
use App\Models\PaymentLink;
use App\Models\Payout;
use App\Models\PayoutRecipient;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalTestingSeeder extends Seeder
{
    /**
     * These credentials are for local and automated testing only.
     * Never call this seeder from production seeders or deployment scripts.
     */
    private const PASSWORD = 'password';

    private const API_SECRETS = [
        'live_sandbox' => 'pay_sk_local_live_sandbox_000000000000000000000000000001',
        'live_production' => 'pay_sk_local_live_production_000000000000000000000000001',
        'sandbox_sandbox' => 'pay_sk_local_sandbox_only_0000000000000000000000000001',
        'suspended_sandbox' => 'pay_sk_local_suspended_00000000000000000000000000001',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! env('LOCAL_TESTING_SEEDER_FORCE', false)) {
            $this->command?->warn('LocalTestingSeeder skipped. Set LOCAL_TESTING_SEEDER_FORCE=true to run outside local/testing.');
            return;
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web', 'description' => 'Platform owner operations access.']
        );
        $merchantRole = Role::firstOrCreate(
            ['name' => 'merchant'],
            ['guard_name' => 'web', 'description' => 'Developer merchant dashboard access.']
        );

        $admin = $this->user('Test Admin', 'admin@test.local');
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $live = $this->merchantUser($merchantRole, [
            'name' => 'Live Merchant',
            'email' => 'live@test.local',
            'public_id' => 'mer_local_live',
            'business_name' => 'Live Merchant Ltd',
            'business_phone' => '254716933897',
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);
        $this->seedLiveMerchant($live);

        $sandbox = $this->merchantUser($merchantRole, [
            'name' => 'Sandbox Merchant',
            'email' => 'sandbox@test.local',
            'public_id' => 'mer_local_sandbox',
            'business_name' => 'Sandbox Merchant Ltd',
            'business_phone' => '254700000002',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => false,
        ]);
        $this->seedSandboxMerchant($sandbox);

        $this->merchantUser($merchantRole, [
            'name' => 'Pending Merchant',
            'email' => 'pending@test.local',
            'public_id' => 'mer_local_pending',
            'business_name' => 'Pending Merchant Ltd',
            'business_phone' => '254700000003',
            'status' => 'active',
            'compliance_status' => 'pending_review',
            'live_enabled' => false,
        ]);

        $suspended = $this->merchantUser($merchantRole, [
            'name' => 'Suspended Merchant',
            'email' => 'suspended@test.local',
            'public_id' => 'mer_local_suspended',
            'business_name' => 'Suspended Merchant Ltd',
            'business_phone' => '254700000004',
            'status' => 'suspended',
            'compliance_status' => 'verified',
            'live_enabled' => false,
        ]);
        $this->createApiKey($suspended, 'Suspended Sandbox Key', 'sandbox', 'suspended_sandbox');

        $this->printCredentials();
    }

    private function user(string $name, string $email): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::PASSWORD,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }

    private function merchantUser(Role $merchantRole, array $data): Merchant
    {
        $user = $this->user($data['name'], $data['email']);
        $user->roles()->syncWithoutDetaching([$merchantRole->id]);

        $merchant = Merchant::updateOrCreate(
            ['user_id' => $user->id],
            [
                'public_id' => $data['public_id'],
                'business_name' => $data['business_name'],
                'business_email' => $data['email'],
                'business_phone' => $data['business_phone'],
                'business_type' => 'limited_company',
                'platform_url' => 'https://'.$data['public_id'].'.example.test',
                'description' => $data['business_name'].' local testing fixture.',
                'status' => $data['status'],
                'compliance_status' => $data['compliance_status'],
                'live_enabled' => $data['live_enabled'],
            ]
        );

        MerchantProfile::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'owner_name' => $data['name'],
                'owner_phone' => $data['business_phone'],
                'owner_email' => $data['email'],
                'document_type' => 'national_id',
                'document_number' => 'LOCAL-TEST-DOC',
                'kra_pin' => 'P000000000L',
                'address' => 'Nairobi, Kenya',
                'terms_accepted_at' => now(),
                'privacy_accepted_at' => now(),
                'notification_email_enabled' => true,
                'notification_sms_enabled' => false,
            ]
        );

        Wallet::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'public_id' => 'wal_'.$data['public_id'],
                'available_balance' => $merchant->public_id === 'mer_local_live' ? 10000 : 0,
                'pending_balance' => 0,
                'currency' => 'KES',
            ]
        );

        MerchantWebhookEndpoint::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'url' => 'https://webhooks.example.test/'.$merchant->public_id,
                'secret' => 'whsec_local_testing_only',
                'is_enabled' => true,
            ]
        );

        return $merchant->refresh();
    }

    private function seedLiveMerchant(Merchant $merchant): void
    {
        $this->createApiKey($merchant, 'Live Sandbox Key', 'sandbox', 'live_sandbox');
        $this->createApiKey($merchant, 'Live Production Key', 'production', 'live_production');

        $success = Transaction::updateOrCreate(
            ['public_id' => 'txn_local_live_success'],
            [
                'merchant_id' => $merchant->id,
                'type' => 'stk_push',
                'direction' => 'credit',
                'environment' => 'production',
                'phone' => '254716933897',
                'amount' => 12000,
                'currency' => 'KES',
                'commission_amount' => 1000,
                'provider_fee' => 0,
                'net_amount' => 11000,
                'status' => 'success',
                'reference' => 'LOCAL-SUCCESS-001',
                'description' => 'Local successful customer payment',
                'mpesa_checkout_request_id' => 'ws_CO_LOCAL_SUCCESS',
                'mpesa_merchant_request_id' => 'mr_LOCAL_SUCCESS',
                'mpesa_receipt_number' => 'RLOCAL001',
                'mpesa_result_code' => '0',
                'mpesa_result_description' => 'The service request is processed successfully.',
                'metadata' => ['source' => 'local_testing'],
                'paid_at' => now(),
            ]
        );

        Transaction::updateOrCreate(
            ['public_id' => 'txn_local_live_pending'],
            [
                'merchant_id' => $merchant->id,
                'type' => 'stk_push',
                'direction' => 'credit',
                'environment' => 'production',
                'phone' => '254716933897',
                'amount' => 2500,
                'currency' => 'KES',
                'net_amount' => 2400,
                'status' => 'pending',
                'reference' => 'LOCAL-PENDING-001',
                'description' => 'Local pending customer payment',
                'mpesa_checkout_request_id' => 'ws_CO_LOCAL_PENDING',
                'metadata' => ['source' => 'local_testing'],
            ]
        );

        Transaction::updateOrCreate(
            ['public_id' => 'txn_local_live_failed'],
            [
                'merchant_id' => $merchant->id,
                'type' => 'stk_push',
                'direction' => 'credit',
                'environment' => 'production',
                'phone' => '254716933897',
                'amount' => 1500,
                'currency' => 'KES',
                'net_amount' => 0,
                'status' => 'failed',
                'reference' => 'LOCAL-FAILED-001',
                'description' => 'Local failed customer payment',
                'mpesa_checkout_request_id' => 'ws_CO_LOCAL_FAILED',
                'mpesa_result_code' => '1032',
                'mpesa_result_description' => 'Request cancelled by user.',
                'failed_at' => now(),
                'metadata' => ['source' => 'local_testing'],
            ]
        );

        $link = $this->paymentLink($merchant, 'plink_local_live', 'local-live-checkout', 'Live Merchant Checkout', 800);
        $invoiceLink = $this->paymentLink($merchant, 'plink_local_invoice', 'local-live-invoice', 'Invoice INV-LOCAL-001', 3000, [
            'invoice_public_id' => 'inv_local_live',
            'invoice_number' => 'INV-LOCAL-001',
        ]);
        $invoice = Invoice::updateOrCreate(
            ['public_id' => 'inv_local_live'],
            [
                'merchant_id' => $merchant->id,
                'payment_link_id' => $invoiceLink->id,
                'invoice_number' => 'INV-LOCAL-001',
                'customer_name' => 'Local Customer',
                'customer_email' => 'customer@example.test',
                'customer_phone' => '254700000010',
                'currency' => 'KES',
                'subtotal' => 3000,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 3000,
                'status' => 'open',
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Local invoice fixture.',
                'sent_at' => now(),
                'metadata' => ['payment_link_public_id' => $invoiceLink->public_id],
            ]
        );
        InvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'description' => 'Integration support'],
            ['quantity' => 1, 'unit_price' => 3000, 'total' => 3000]
        );

        $recipient = PayoutRecipient::updateOrCreate(
            ['public_id' => 'rec_local_live'],
            [
                'merchant_id' => $merchant->id,
                'name' => 'Local Recipient',
                'phone' => '254700000020',
                'status' => 'active',
                'metadata' => ['source' => 'local_testing'],
            ]
        );

        $payout = Payout::updateOrCreate(
            ['public_id' => 'po_local_live_success'],
            [
                'merchant_id' => $merchant->id,
                'payout_recipient_id' => $recipient->id,
                'amount' => 1000,
                'currency' => 'KES',
                'fee' => 0,
                'net_amount' => 1000,
                'phone' => $recipient->phone,
                'status' => 'success',
                'provider' => 'mpesa',
                'provider_conversation_id' => 'AG_LOCAL_PAYOUT',
                'provider_result_code' => '0',
                'provider_result_description' => 'Local payout success.',
                'requested_at' => now(),
                'processed_at' => now(),
                'paid_at' => now(),
                'metadata' => ['source' => 'local_testing'],
            ]
        );

        $wallet = $merchant->wallet()->firstOrFail();
        WalletLedgerEntry::updateOrCreate(
            ['public_id' => 'wle_local_live_payment_credit'],
            [
                'wallet_id' => $wallet->id,
                'merchant_id' => $merchant->id,
                'transaction_id' => $success->id,
                'entry_type' => 'payment_credit',
                'direction' => 'credit',
                'amount' => 11000,
                'balance_after' => 11000,
                'description' => 'Local successful transaction net credit.',
                'metadata' => ['source' => 'txn_local_live_success'],
            ]
        );
        WalletLedgerEntry::updateOrCreate(
            ['public_id' => 'wle_local_live_payout_debit'],
            [
                'wallet_id' => $wallet->id,
                'merchant_id' => $merchant->id,
                'transaction_id' => null,
                'entry_type' => 'payout_debit',
                'direction' => 'debit',
                'amount' => $payout->amount,
                'balance_after' => 10000,
                'description' => 'Local sample payout debit.',
                'metadata' => ['payout_public_id' => $payout->public_id],
            ]
        );

        $wallet->update(['available_balance' => 10000, 'pending_balance' => 0]);
    }

    private function seedSandboxMerchant(Merchant $merchant): void
    {
        $this->createApiKey($merchant, 'Sandbox Only Key', 'sandbox', 'sandbox_sandbox');
        $link = $this->paymentLink($merchant, 'plink_local_sandbox', 'local-sandbox-checkout', 'Sandbox Checkout', 500);
        $invoiceLink = $this->paymentLink($merchant, 'plink_local_sandbox_invoice', 'local-sandbox-invoice', 'Sandbox Invoice INV-SANDBOX-001', 1200, [
            'invoice_public_id' => 'inv_local_sandbox',
            'invoice_number' => 'INV-SANDBOX-001',
        ]);

        $invoice = Invoice::updateOrCreate(
            ['public_id' => 'inv_local_sandbox'],
            [
                'merchant_id' => $merchant->id,
                'payment_link_id' => $invoiceLink->id,
                'invoice_number' => 'INV-SANDBOX-001',
                'customer_name' => 'Sandbox Customer',
                'customer_email' => 'sandbox-customer@example.test',
                'customer_phone' => '254700000030',
                'currency' => 'KES',
                'subtotal' => 1200,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 1200,
                'status' => 'draft',
                'due_date' => now()->addDays(14)->toDateString(),
                'metadata' => ['payment_link_public_id' => $invoiceLink->public_id],
            ]
        );
        InvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'description' => 'Sandbox service'],
            ['quantity' => 1, 'unit_price' => 1200, 'total' => 1200]
        );
    }

    private function createApiKey(Merchant $merchant, string $name, string $environment, string $secretKey): ApiKey
    {
        $secret = self::API_SECRETS[$secretKey];

        return ApiKey::updateOrCreate(
            ['publishable_key' => 'pay_pk_'.$secretKey],
            [
                'merchant_id' => $merchant->id,
                'name' => $name,
                'environment' => $environment,
                'secret_key_hash' => Hash::make($secret),
                'secret_key_prefix' => substr($secret, 0, 10),
                'secret_key_last_four' => substr($secret, -4),
                'status' => 'active',
                'revoked_at' => null,
            ]
        );
    }

    private function paymentLink(Merchant $merchant, string $publicId, string $slug, string $title, int $amount, array $metadata = []): PaymentLink
    {
        return PaymentLink::updateOrCreate(
            ['public_id' => $publicId],
            [
                'merchant_id' => $merchant->id,
                'slug' => $slug,
                'title' => $title,
                'description' => $title.' local testing payment link.',
                'amount' => $amount,
                'currency' => 'KES',
                'allow_custom_amount' => false,
                'success_redirect_url' => null,
                'status' => 'active',
                'metadata' => ['source' => 'local_testing', ...$metadata],
            ]
        );
    }

    private function printCredentials(): void
    {
        $this->command?->info('Local testing users ready. Password for all users: password');
        $this->command?->line('admin@test.local / password');
        $this->command?->line('live@test.local / password');
        $this->command?->line('sandbox@test.local / password');
        $this->command?->line('pending@test.local / password');
        $this->command?->line('suspended@test.local / password');
        $this->command?->newLine();
        $this->command?->warn('Local API secrets are printed for local testing only. They are stored hashed in the database.');
        $this->command?->line('Live sandbox secret: '.self::API_SECRETS['live_sandbox']);
        $this->command?->line('Live production secret: '.self::API_SECRETS['live_production']);
        $this->command?->line('Sandbox-only secret: '.self::API_SECRETS['sandbox_sandbox']);
        $this->command?->line('Suspended merchant secret: '.self::API_SECRETS['suspended_sandbox']);
    }
}
