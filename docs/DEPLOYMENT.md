# Deployment

## Server Requirements

- PHP matching `composer.json` (`^8.3`).
- Composer.
- Node.js and npm for asset builds.
- A production database such as MySQL, PostgreSQL, or managed equivalent.
- Queue driver: database, Redis, SQS, or another worker-backed driver.
- Redis is recommended if used for cache/queue/session.
- HTTPS with a valid TLS certificate.
- A process manager for queue workers.

## Install Steps

```bash
git clone <repository-url> payment-gateway
cd payment-gateway
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cp .env.example .env
php artisan key:generate
```

Edit `.env` with production values. Do not commit `.env`.

## Database

```bash
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
```

Set `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, and `INITIAL_ADMIN_PASSWORD` before running the admin seeder. Remove the password value from shell history or deployment tooling after use.

## Cache And Optimization

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Storage And Permissions

Make sure the web server user can write to:

- `storage/`
- `bootstrap/cache/`

Use your hosting platform's recommended ownership and permission model.

## Queue Worker

Run queue workers with Supervisor, systemd, Horizon, or platform workers:

```bash
php artisan queue:work --queue=webhooks,payouts,default --tries=3 --timeout=120 --memory=256
```

## Scheduler

Add one cron entry:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks include stale transaction expiration, idempotency cleanup, operations alerts, security checks, and reconciliation reporting.

## Web Server Notes

Point nginx or Apache to the `public/` directory. Enforce HTTPS and forward standard headers such as `X-Forwarded-Proto` when behind a load balancer.

## Validation

```bash
php artisan payments:security-check
php artisan operations:check-alerts
php artisan payments:prelive-check
php artisan test
```

## Rollback Notes

- Keep the previous release directory until smoke checks pass.
- Back up the database before migrations.
- Roll back code first, then only roll back migrations if the migration is known to be reversible and no production data would be lost.
- Do not manually edit wallet balances or ledger rows during rollback.
For containerized deployment without Nginx, see [DOCKER.md](DOCKER.md). The Docker setup uses FrankenPHP, assumes PostgreSQL already exists, and runs separate app, queue, and scheduler services from one image.

