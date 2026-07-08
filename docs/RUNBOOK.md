# Operations Runbook

## Restart Queue Workers

Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart payment-gateway-worker:*
```

systemd:

```bash
sudo systemctl restart payment-gateway-worker
sudo systemctl status payment-gateway-worker
```

## Check Failed Jobs

```bash
php artisan queue:failed
```

Admin UI:

```text
/admin/failed-jobs
```

## Reconciliation Report

```bash
php artisan payments:reconciliation-report
php artisan payments:reconciliation-report --csv=/tmp/reconciliation.csv
```

## Expire Stale Transactions

```bash
php artisan payments:expire-pending-transactions
```

This marks old pending transactions as `timeout`; it does not credit wallets.

## Check Operations Alerts

```bash
php artisan operations:check-alerts
```

## Verify M-Pesa Callbacks

- Confirm `MPESA_CALLBACK_SECRET` is set.
- Confirm callback URLs match `/api/mpesa/stk-callback/{secret}` and B2C result/timeout URLs.
- Check `/admin/mpesa-callbacks`.
- Check transaction status and callback result code.

## Missing Wallet Credit

- Confirm the transaction is `success`.
- Confirm an M-Pesa callback exists.
- Confirm wallet ledger has a `payment_credit` entry for the transaction.
- Do not create manual wallet rows.
- Use reconciliation report to compare net credited and ledger credits.

## Failed Webhook

- Check `/admin/webhook-deliveries`.
- Confirm merchant webhook URL and secret are configured.
- Confirm merchant endpoint verifies raw JSON signatures.
- Retry only through the admin retry action.

## Failed Payout

- Check `/admin/payouts`.
- Confirm payout status and failure reason.
- Confirm failed payout created exactly one `payout_reversal` ledger entry.
- Do not manually reverse wallet balances.

## What Not To Do

- Do not manually edit wallet balances.
- Do not manually insert ledger entries outside approved services.
- Do not expose API secrets, M-Pesa secrets, webhook secrets, or callback secrets.
- Do not retry callbacks by duplicating wallet credits.
- Do not run destructive database actions without a backup and rollback plan.

