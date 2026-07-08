# Local Testing Fixtures

Use this seeder only for local development or automated testing. It creates known users, merchant states, API keys, wallets, transactions, invoices, payouts, and webhook settings for quick manual checks.

```bash
php artisan db:seed --class=LocalTestingSeeder
```

The seeder is guarded to `local` and `testing` environments. To run it elsewhere for a temporary staging smoke test, set `LOCAL_TESTING_SEEDER_FORCE=true` deliberately. Do not include it in production seeders or deployment scripts.

## Test Logins

All local users use the password `password`.

| Role | Email |
| --- | --- |
| Admin | `admin@test.local` |
| Verified live merchant | `live@test.local` |
| Sandbox-only merchant | `sandbox@test.local` |
| Pending compliance merchant | `pending@test.local` |
| Suspended merchant | `suspended@test.local` |

The seeder prints local-only API secrets after it runs. They are stored hashed in the database and are intended only for local API smoke tests.

## Checklist

1. Login as `admin@test.local` and confirm the admin sidebar shows Overview, Merchants, Transactions, Wallets / Ledger, Payouts, M-Pesa Callbacks, Webhook Deliveries, Failed Jobs, Pre-Live Check, and Audit Logs.
2. Login as `live@test.local` and confirm the developer sidebar shows Dashboard, Onboarding, API Keys, Wallet, Transactions, Payment Links, Invoices, Payouts, Webhooks, API Docs, and Compliance.
3. Login as `sandbox@test.local` and confirm production key creation is blocked until live mode is enabled.
4. Login as `suspended@test.local` and confirm API requests using its local secret are rejected.
5. Create a payment link.
6. Create an invoice.
7. Request a payout.
8. View webhook settings.
9. View API docs.
10. Confirm admin can see merchants, transactions, wallets, payouts, callbacks, webhook deliveries, failed jobs, pre-live checks, and audit logs.
