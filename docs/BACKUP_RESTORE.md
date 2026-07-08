# Backup And Restore

## Database Backup

Use your database engine's native tooling.

PostgreSQL placeholder:

```bash
pg_dump "$DATABASE_URL" > backup_$(date +%F).sql
```

MySQL placeholder:

```bash
mysqldump -h <host> -u <user> -p <database> > backup_$(date +%F).sql
```

## Storage Backup

Back up uploaded files and any private storage directories if your deployment uses them:

```bash
tar -czf storage_backup_$(date +%F).tar.gz storage/app
```

## Environment Backup Warning

Back up `.env` securely and separately. It contains secrets. Do not store it in the repository or unencrypted shared folders.

## Restore Steps

1. Stop queue workers.
2. Put the app in maintenance mode.
3. Restore the database backup.
4. Restore storage files.
5. Restore the correct `.env`.
6. Run `php artisan optimize:clear`.
7. Run `php artisan payments:prelive-check`.
8. Restart queue workers.
9. Disable maintenance mode.

## Test Restore

Test restores regularly in a non-production environment. A backup that has never been restored is not proven.

## Wallet And Ledger Integrity

Wallet balances are ledger-backed. Never edit wallet balances or ledger entries manually during restore. Restore the database as a consistent snapshot and use reconciliation reports to detect differences.

