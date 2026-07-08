# Production Readiness

## Environment Checklist

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set a real `APP_KEY`.
- Set `APP_URL=https://your-domain.example`.
- Use a durable database, cache, session, and queue backend.
- Set `QUEUE_CONNECTION` to `database`, `redis`, `sqs`, or another worker-backed driver. Do not use `sync` in production.
- Set `MPESA_ENV=production` only after live credentials are issued.
- Configure `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, `MPESA_CALLBACK_URL`, and `MPESA_CALLBACK_SECRET`.
- Keep `MPESA_B2C_FAKE=true` until real B2C credentials are ready and tested.
- Configure mail before relying on invoice email delivery.

## Queue Workers

Run workers for the queues used by payment jobs:

```bash
php artisan queue:work --queue=webhooks,payouts,default --tries=3
```

Use Supervisor, systemd, Horizon, or your platform process manager so workers restart after deployment.

## Scheduler

Add one cron entry on the server:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled payment operations:

- `payments:expire-pending-transactions` every five minutes.
- `payments:cleanup-idempotency-keys` daily.
- `payments:reconciliation-report` daily.
- `payments:security-check` daily.
- `operations:check-alerts` every five minutes.

See `docs/DEPLOYMENT.md`, `docs/RUNBOOK.md`, and `docs/BACKUP_RESTORE.md` before launch.

## M-Pesa Callback URLs

STK callback format:

```text
https://your-domain.example/api/mpesa/stk-callback/{MPESA_CALLBACK_SECRET}
```

B2C result and timeout callback formats:

```text
https://your-domain.example/api/mpesa/b2c/result/{MPESA_CALLBACK_SECRET}
https://your-domain.example/api/mpesa/b2c/timeout/{MPESA_CALLBACK_SECRET}
```

Rotate the callback secret if it is ever exposed.

## Pre-Live Testing Flow

- Run `php artisan payments:security-check`.
- Run `php artisan test`.
- Run `npm run build`.
- Complete the merchant onboarding checklist.
- Submit KYC/compliance information and verify it from the admin merchant page.
- Confirm terms and privacy acceptance timestamps are present.
- Request live mode from `/developer/onboarding`.
- Approve live mode from the admin merchant page only after compliance and operational checks pass.
- Create sandbox API keys and initiate an STK Push.
- Confirm Safaricom callback updates the transaction once.
- Confirm wallet credit appears only after callback success.
- Confirm webhook delivery signatures verify on the merchant endpoint.
- Test payout fake success and failure before enabling real B2C.

## Backups and Monitoring

- Back up the database before and after deployments.
- Monitor queue depth, failed jobs, webhook delivery failures, payout failures, and stale pending transactions.
- Review `/admin/failed-jobs` regularly.
- Run `php artisan operations:check-alerts` through the scheduler or manually during incident response.
- Store logs securely and avoid shipping secrets to third-party log tools.
- Keep database backups encrypted and test restore procedures.

## Live Mode Approval Checklist

- Business profile is complete.
- Owner/contact information is complete.
- At least one sandbox API key exists.
- Sandbox transaction has been attempted.
- Webhook endpoint is configured if the merchant needs callbacks.
- Compliance status is `verified` or, at minimum, `pending_review` before request intake.
- Legal, privacy, and acceptable-use pages have been reviewed by counsel.
- Queue workers and scheduler are running.
- Failed jobs and operational alerts are monitored.
- Database backup and restore process has been tested.

## Compliance Reminder

This project handles payment data and phone numbers. Add legal terms, privacy notices, data retention policy, merchant KYC/compliance workflow, and operational incident response before processing real customer funds.
