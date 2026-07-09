# Docker Deployment

This project ships with a production-capable Docker image that uses FrankenPHP to serve Laravel directly. It does not include Nginx and it does not start PostgreSQL. Point the app at your already configured PostgreSQL database through `.env`.

## Files

- `Dockerfile` builds PHP dependencies, frontend assets, and the final runtime image.
- `docker-compose.yml` runs three services from the same image: `app`, `queue`, and `scheduler`.
- `.env.docker.example` shows production-oriented environment values for an external PostgreSQL database.
- `docker/frankenphp/Caddyfile` serves `/public` through FrankenPHP on port `8000`.
- `docker/entrypoint.sh` handles app, queue, scheduler, and one-off artisan commands.

## Prepare Environment

Copy the Docker example and fill real values:

```bash
copy .env.docker.example .env
```

Set at minimum:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://your-domain.example

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your-database
DB_USERNAME=your-user
DB_PASSWORD=your-password

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

Generate `APP_KEY` outside the production container if you do not already have one:

```bash
php artisan key:generate --show
```

Do not commit `.env`.

## Build

```bash
docker compose build
```

## First Production Run

Run migrations explicitly:

```bash
docker compose run --rm app artisan migrate --force
```

Create the first admin only after setting `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, and `INITIAL_ADMIN_PASSWORD`:

```bash
docker compose run --rm app artisan db:seed --class=AdminUserSeeder --force
```

Run the safety checks:

```bash
docker compose run --rm app artisan payments:security-check
docker compose run --rm app artisan payments:prelive-check
```

Start the services:

```bash
docker compose up -d
```

## Services

`app` serves HTTP on `${APP_PORT:-8000}`.

`queue` runs:

```bash
php artisan queue:work --queue=webhooks,payouts,default
```

Override queues and worker settings through:

```env
QUEUE_NAMES=webhooks,payouts,default
QUEUE_TRIES=3
QUEUE_TIMEOUT=120
QUEUE_MEMORY=256
QUEUE_SLEEP=3
```

`scheduler` runs `php artisan schedule:run` every 60 seconds. Override with:

```env
SCHEDULER_SLEEP=60
```

## Optional Auto-Run Flags

The image supports these flags, but keep them disabled unless your deployment process deliberately wants container startup to mutate the database:

```env
RUN_MIGRATIONS=false
RUN_ADMIN_SEEDER=false
```

For production, explicit one-off commands are usually safer than automatic migrations on every container boot.

## Existing PostgreSQL

No `postgres` service is included. Your PostgreSQL server must already be reachable from the Docker host/network. If the database is on the host machine, use the host address supported by your Docker environment. On Docker Desktop, this is often:

```env
DB_HOST=host.docker.internal
```

On Linux servers, prefer a real private network DNS name or IP address.

## Useful Commands

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f queue
docker compose exec app php artisan about
docker compose exec app php artisan payments:prelive-check
docker compose exec app php artisan operations:check-alerts
docker compose exec app php artisan payments:reconciliation-report
docker compose restart queue scheduler
```

## Updating

```bash
docker compose build
docker compose run --rm app artisan migrate --force
docker compose up -d
docker compose exec app php artisan payments:prelive-check
```

## Notes

- Terminate TLS either at your platform load balancer, reverse proxy, or a managed ingress. The container itself listens on HTTP port `8000`.
- Set M-Pesa callback URLs to the public HTTPS domain, not the internal Docker hostname.
- Keep `MPESA_B2C_FAKE=false` in real production once B2C credentials are live.
- Keep `LocalTestingSeeder` out of production. It is only for local/testing fixtures.
